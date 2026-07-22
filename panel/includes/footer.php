<script data-panel-core="true">
    function onReady(callback) {
        var intervalID = window.setInterval(checkReady, 1000);
        function checkReady() {
            if (document.getElementsByTagName('body')[0] !== undefined) {
                window.clearInterval(intervalID);
                callback.call(this);
            }
        }
    }

    function show(id, value) {
        document.getElementById(id).style.display = value ? 'block' : 'none';
    }

    onReady(function () {
        show('page', true);
        show('loading', false);
    });
</script>
<div class="panel-loading-dialog" id="panelLoadingDialog" aria-hidden="true">
    <div class="panel-loading-card">
        <span class="panel-loading-spinner"></span>
        <strong id="panelLoadingText">Saving changes...</strong>
        <small>Please wait while we update the panel.</small>
    </div>
</div>
<div class="panel-toast" id="panelToast" aria-live="polite" aria-atomic="true"></div>
<script data-panel-core="true">
    (function () {
        var loadingDialog = document.getElementById('panelLoadingDialog');
        var loadingText = document.getElementById('panelLoadingText');
        var toast = document.getElementById('panelToast');

        function setLoadingState(visible, message) {
            if (!loadingDialog) {
                return;
            }

            if (message && loadingText) {
                loadingText.textContent = message;
            }

            loadingDialog.classList.toggle('is-visible', visible);
            loadingDialog.setAttribute('aria-hidden', visible ? 'false' : 'true');
        }

        function showToast(message, type) {
            if (!toast) {
                return;
            }

            toast.textContent = message;
            toast.className = 'panel-toast is-visible panel-toast-' + (type || 'success');
            window.clearTimeout(showToast._timer);
            showToast._timer = window.setTimeout(function () {
                toast.className = 'panel-toast';
            }, 2800);
        }

async function postForm(url, formData, options) {
            options = options || {};
            setLoadingState(true, options.loadingText || 'Saving changes...');

            try {
                var response = await fetch(url, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                var contentType = response.headers.get('content-type');
                if (!contentType || contentType.indexOf('application/json') === -1) {
                    var text = await response.text();
                    console.log('Non-JSON response:', text);
                    throw new Error('Invalid server response');
                }
                
                var payload = await response.json();

                if (!response.ok || !payload.success) {
                    throw new Error(payload.message || 'Something went wrong.');
                }

                if (options.successMessage !== false) {
                    showToast(options.successMessage || payload.message || 'Saved successfully.', 'success');
                }

                return payload;
            } catch (error) {
                showToast(error.message || 'Something went wrong.', 'error');
                throw error;
            } finally {
                setLoadingState(false);
            }
        }

        function renumberTableRows(tableSelector) {
            var table = document.querySelector(tableSelector);
            if (!table) {
                return;
            }

            table.querySelectorAll('tbody tr').forEach(function (row, index) {
                var numberCell = row.querySelector('.js-row-number');
                if (numberCell) {
                    numberCell.textContent = index + 1;
                }
            });
        }

        function closeModal(modalSelector) {
            var modal = document.querySelector(modalSelector);
            var cleanupModalArtifacts = function () {
                if (modal) {
                    modal.classList.remove('in');
                    modal.setAttribute('aria-hidden', 'true');
                    modal.style.display = 'none';
                }

                document.body.classList.remove('modal-open');
                document.body.style.paddingRight = '';
                document.body.style.overflow = '';

                document.querySelectorAll('.modal-backdrop').forEach(function (backdrop) {
                    backdrop.classList.remove('in');
                    backdrop.classList.remove('fade');
                    backdrop.style.display = 'none';
                    if (backdrop.parentNode) {
                        backdrop.parentNode.removeChild(backdrop);
                    }
                });
            };

            cleanupModalArtifacts();

            if (window.jQuery && modal) {
                window.jQuery(modal).one('hidden.bs.modal', cleanupModalArtifacts);
                window.jQuery(modal).modal('hide');
            }

            window.setTimeout(cleanupModalArtifacts, 50);
            window.setTimeout(cleanupModalArtifacts, 250);
        }

        function openModal(modalSelector) {
            var modal = document.querySelector(modalSelector);
            if (!modal) {
                return;
            }

            document.querySelectorAll('.modal-backdrop').forEach(function (backdrop) {
                backdrop.remove();
            });

            if (window.jQuery) {
                try {
                    window.jQuery(modal).modal('show');
                    return;
                } catch (error) {
                }
            }

            var backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade in';
            document.body.appendChild(backdrop);
            document.body.classList.add('modal-open');
            document.body.style.overflow = 'hidden';
            modal.style.display = 'block';
            modal.classList.add('in');
            modal.setAttribute('aria-hidden', 'false');
        }

        window.panelApp = {
            closeModal: closeModal,
            openModal: openModal,
            postForm: postForm,
            renumberTableRows: renumberTableRows,
            setLoading: setLoadingState,
            showToast: showToast
        };
    })();
</script>
<script data-panel-core="true">
    (function () {
        if (!document.body.classList.contains('cbp-spmenu-push')) {
            return;
        }

        var currentRequest = null;
        var currentPath = window.location.pathname.split('/').pop() || 'dashboard.php';
        var shellWrappers = ['#page-wrapper', '.sticky-header', '.sidebar'];
        var sidebarInitialized = false;

        function closeOpenDropdowns() {
            document.querySelectorAll('.dropdown.open').forEach(function (dropdown) {
                dropdown.classList.remove('open');
                var toggle = dropdown.querySelector('[data-toggle="dropdown"]');
                if (toggle) {
                    toggle.setAttribute('aria-expanded', 'false');
                }
            });
        }

        function isSpaEligibleUrl(url) {
            if (!url) {
                return false;
            }

            try {
                var parsed = new URL(url, window.location.href);
                var sameOrigin = parsed.origin === window.location.origin;
                var isPanelPage = parsed.pathname.indexOf('/panel/') !== -1;
                var isPhpPage = parsed.pathname.endsWith('.php');
                var isLogout = parsed.pathname.endsWith('/logout.php');
                var hasDownload = parsed.searchParams.has('download') || parsed.searchParams.has('export');

                return sameOrigin && isPanelPage && isPhpPage && !isLogout && !hasDownload;
            } catch (error) {
                return false;
            }
        }

        function markActiveLink(targetPath) {
            document.querySelectorAll('.sidebar a').forEach(function (link) {
                var href = link.getAttribute('href') || '';
                var normalized = href.split('?')[0];
                var isMatch = normalized === targetPath;
                link.classList.toggle('active', isMatch);

                var item = link.closest('li');
                if (item) {
                    item.classList.toggle('active', isMatch);
                }

                var parentMenu = link.closest('.nav-second-level');
                if (parentMenu && isMatch) {
                    parentMenu.classList.add('in');
                    var parentTrigger = parentMenu.previousElementSibling;
                    if (parentTrigger) {
                        parentTrigger.classList.add('active');
                        var parentItem = parentTrigger.closest('li');
                        if (parentItem) {
                            parentItem.classList.add('active');
                        }
                    }
                }
            });
        }

        function initializeSidebarInteractions() {
            var sidebar = document.querySelector('.sidebar');
            if (!sidebar) {
                return;
            }

            if (!sidebarInitialized) {
                sidebar.addEventListener('click', function (event) {
                    var trigger = event.target.closest('.sidebar a');
                    if (!trigger) {
                        return;
                    }

                    var submenu = trigger.nextElementSibling;
                    if (!submenu || !submenu.classList.contains('nav-second-level')) {
                        return;
                    }

                    var href = trigger.getAttribute('href') || '';
                    var clickedArrow = event.target.closest('.arrow');

                    if (href !== '#' && href !== '' && !clickedArrow) {
                        return;
                    }

                    event.preventDefault();

                    var parentItem = trigger.closest('li');
                    var isOpen = submenu.classList.contains('in');

                    sidebar.querySelectorAll('.nav-second-level.in').forEach(function (menu) {
                        if (menu !== submenu) {
                            menu.classList.remove('in');
                            var openParent = menu.closest('li');
                            if (openParent) {
                                openParent.classList.remove('active');
                            }
                            var openTrigger = menu.previousElementSibling;
                            if (openTrigger) {
                                openTrigger.classList.remove('active');
                            }
                        }
                    });

                    submenu.classList.toggle('in', !isOpen);
                    if (parentItem) {
                        parentItem.classList.toggle('active', !isOpen);
                    }
                    trigger.classList.toggle('active', !isOpen);
                });

                sidebarInitialized = true;
            }
        }

        function cleanupTransientUi() {
            document.querySelectorAll('.modal-backdrop').forEach(function (backdrop) {
                backdrop.remove();
            });
            document.body.classList.remove('modal-open');
            document.body.style.paddingRight = '';
            document.body.style.overflow = '';
        }

        function replacePageWrapper(nextDocument) {
            var nextWrapper = nextDocument.querySelector('#page-wrapper');
            var currentWrapper = document.querySelector('#page-wrapper');

            if (!nextWrapper || !currentWrapper) {
                throw new Error('Could not load this page into the SPA shell.');
            }

            currentWrapper.className = nextWrapper.className;
            currentWrapper.innerHTML = nextWrapper.innerHTML;
        }

        function replacePageModals(nextDocument) {
            document.querySelectorAll('.modal').forEach(function (modal) {
                modal.parentNode.removeChild(modal);
            });

            nextDocument.querySelectorAll('.modal').forEach(function (modal) {
                document.body.appendChild(modal.cloneNode(true));
            });
        }

        function replaceHeadTitle(nextDocument) {
            var nextTitle = nextDocument.querySelector('title');
            if (nextTitle) {
                document.title = nextTitle.textContent;
            }
        }

        function replaceShellFragments(nextDocument) {
            shellWrappers.forEach(function (selector) {
                if (selector === '#page-wrapper') {
                    return;
                }

                var currentNode = document.querySelector(selector);
                var nextNode = nextDocument.querySelector(selector);

                if (currentNode && nextNode) {
                    currentNode.innerHTML = nextNode.innerHTML;
                }
            });
        }

        function loadScript(oldScript) {
            return new Promise(function (resolve, reject) {
                var script = document.createElement('script');

                Array.prototype.slice.call(oldScript.attributes).forEach(function (attribute) {
                    script.setAttribute(attribute.name, attribute.value);
                });

                if (oldScript.src) {
                    script.onload = resolve;
                    script.onerror = reject;
                    script.src = oldScript.src;
                } else {
                    script.textContent = oldScript.textContent;
                    resolve();
                }

                document.body.appendChild(script);
            });
        }

        async function runPageScripts(nextDocument) {
            var scripts = Array.prototype.slice.call(nextDocument.querySelectorAll('script'));
            for (var index = 0; index < scripts.length; index += 1) {
                var oldScript = scripts[index];
                if (oldScript.dataset.panelCore === 'true' || oldScript.closest('#panelLoadingDialog') || oldScript.closest('#panelToast')) {
                    continue;
                }
                if ((oldScript.src || '').indexOf('translate.google.com') !== -1) {
                    continue;
                }
                await loadScript(oldScript);
            }
        }

        async function navigate(url, options) {
            options = options || {};
            var parsed = new URL(url, window.location.href);
            var targetPath = parsed.pathname.split('/').pop() || 'dashboard.php';

            if (!isSpaEligibleUrl(parsed.toString())) {
                window.location.href = parsed.toString();
                return;
            }

            if (currentRequest) {
                currentRequest.abort();
            }

            currentRequest = new AbortController();
            panelApp.setLoading(true, options.loadingText || 'Loading page...');

            try {
                var response = await fetch(parsed.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-SPA-Request': 'true'
                    },
                    signal: currentRequest.signal
                });
                var html = await response.text();
                var parser = new DOMParser();
                var nextDocument = parser.parseFromString(html, 'text/html');

                replaceHeadTitle(nextDocument);
                replaceShellFragments(nextDocument);
                replacePageWrapper(nextDocument);
                replacePageModals(nextDocument);
                cleanupTransientUi();
                initializeSidebarInteractions();
                if (typeof window.initDrawer === 'function') {
                    window.initDrawer();
                }
                await runPageScripts(nextDocument);

                if (typeof $.fn.metisMenu === 'function') {
                    $('#side-menu, .navigation, #cbp-spmenu-s1').metisMenu('dispose').metisMenu();
                }

                if (!options.replaceState) {
                    window.history.pushState({ url: parsed.toString() }, '', parsed.toString());
                } else {
                    window.history.replaceState({ url: parsed.toString() }, '', parsed.toString());
                }

                currentPath = targetPath;
                markActiveLink(currentPath);
            } catch (error) {
                if (error.name !== 'AbortError') {
                    panelApp.showToast(error.message || 'Page load failed.', 'error');
                }
            } finally {
                panelApp.setLoading(false);
            }
        }

        document.addEventListener('click', function (event) {
            var dropdownToggle = event.target.closest('[data-toggle="dropdown"]');
            if (dropdownToggle) {
                var dropdown = dropdownToggle.closest('.dropdown');
                if (dropdown) {
                    event.preventDefault();
                    event.stopPropagation();
                    if (typeof event.stopImmediatePropagation === 'function') {
                        event.stopImmediatePropagation();
                    }
                    var isOpen = dropdown.classList.contains('open');
                    closeOpenDropdowns();
                    dropdown.classList.toggle('open', !isOpen);
                    dropdownToggle.setAttribute('aria-expanded', !isOpen ? 'true' : 'false');
                }
                return;
            }

            var modalTrigger = event.target.closest('[data-toggle="modal"][data-target]');
            if (modalTrigger) {
                var targetSelector = modalTrigger.getAttribute('data-target');
                var targetModal = document.querySelector(targetSelector);

                if (targetModal) {
                    event.preventDefault();
                    event.stopPropagation();
                    if (typeof event.stopImmediatePropagation === 'function') {
                        event.stopImmediatePropagation();
                    }
                    cleanupTransientUi();
                    panelApp.openModal(targetSelector);
                }
                return;
            }

            var modalDismiss = event.target.closest('[data-dismiss="modal"]');
            if (modalDismiss) {
                var dismissModal = modalDismiss.closest('.modal');
                if (dismissModal) {
                    event.preventDefault();
                    event.stopPropagation();
                    if (typeof event.stopImmediatePropagation === 'function') {
                        event.stopImmediatePropagation();
                    }
                    panelApp.closeModal('#' + dismissModal.id);
                }
                return;
            }

            if (event.defaultPrevented) {
                return;
            }

            var link = event.target.closest('a[href]');
            if (!link) {
                return;
            }

            if (link.target === '_blank' || link.hasAttribute('download') || event.metaKey || event.ctrlKey || event.shiftKey) {
                return;
            }

            var href = link.getAttribute('href');
            if (!isSpaEligibleUrl(href)) {
                return;
            }

            event.preventDefault();
            navigate(href);
        });

        document.addEventListener('click', function (event) {
            if (!event.target.closest('.dropdown')) {
                closeOpenDropdowns();
            }
        });

        document.addEventListener('submit', function (event) {
            var form = event.target;
            if (!form || form.method.toLowerCase() !== 'get') {
                return;
            }

            var action = form.getAttribute('action') || window.location.href;
            if (!isSpaEligibleUrl(action)) {
                return;
            }

            event.preventDefault();
            var query = new URLSearchParams(new FormData(form)).toString();
            navigate(action + (query ? ((action.indexOf('?') === -1 ? '?' : '&') + query) : ''), {
                loadingText: 'Loading results...'
            });
        });

        window.addEventListener('popstate', function (event) {
            var target = event.state && event.state.url ? event.state.url : window.location.href;
            navigate(target, {
                replaceState: true,
                loadingText: 'Loading page...'
            });
        });

        window.panelSpa = {
            initializeSidebarInteractions: initializeSidebarInteractions,
            navigate: navigate,
            markActiveLink: markActiveLink
        };

        window.history.replaceState({ url: window.location.href }, '', window.location.href);
        initializeSidebarInteractions();
        markActiveLink(currentPath);
    })();
</script>
