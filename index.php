<?php
include('panel/includes/dbconnection.php');

$serviceCount = mysqli_num_rows(mysqli_query($con, "select * from tblservices"));
$taxCount = mysqli_num_rows(mysqli_query($con, "select * from tbl_tax"));
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Home | Marie Noelle Spa and Salon</title>
  <meta name="description"
    content="Step into a world of tranquility at Marie Noelle Spa and Salon, where self-care meets luxury, beauty, and serene wellness experiences." />
  <link rel="icon" href="panel/images/logo.png" type="image/x-icon">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:wght@400;700&family=Manrope:wght@400;500;600;700;800&display=swap"
    rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

  <style>
    :root {
      --bg: #f7efe6;
      --bg-soft: #fbf6f1;
      --surface: rgba(255, 255, 255, 0.78);
      --surface-strong: #ffffff;
      --text: #2e221c;
      --muted: #77675b;
      --border: rgba(62, 45, 35, 0.12);
      --accent: #b18458;
      --accent-dark: #8f6541;
      --accent-soft: #efe0cf;
      --forest: #2b5b55;
      --shadow: 0 24px 80px rgba(62, 45, 35, 0.12);
      --shadow-soft: 0 14px 36px rgba(62, 45, 35, 0.08);
      --radius-xl: 36px;
      --radius-lg: 24px;
      --radius-md: 18px;
    }

    * {
      box-sizing: border-box;
    }

    html {
      scroll-behavior: smooth;
    }

    body {
      margin: 0;
      font-family: "Manrope", sans-serif;
      color: var(--text);
      background:
        radial-gradient(circle at top left, rgba(177, 132, 88, 0.18), transparent 26%),
        radial-gradient(circle at 90% 10%, rgba(43, 91, 85, 0.14), transparent 20%),
        linear-gradient(180deg, #fcf7f2 0%, #f5ece3 100%);
    }

    a {
      color: inherit;
      text-decoration: none;
    }

    img {
      max-width: 100%;
      display: block;
    }

    .page-shell {
      position: relative;
      overflow: hidden;
    }

    .page-shell::before,
    .page-shell::after {
      content: "";
      position: fixed;
      z-index: 0;
      border-radius: 999px;
      pointer-events: none;
      filter: blur(18px);
      opacity: 0.55;
    }

    .page-shell::before {
      top: 120px;
      left: -120px;
      width: 320px;
      height: 320px;
      background: rgba(177, 132, 88, 0.18);
      animation: floatBlob 14s ease-in-out infinite;
    }

    .page-shell::after {
      top: 38%;
      right: -110px;
      width: 280px;
      height: 280px;
      background: rgba(43, 91, 85, 0.16);
      animation: floatBlob 18s ease-in-out infinite reverse;
    }

    .container-shell {
      width: min(1200px, calc(100% - 32px));
      margin: 0 auto;
    }

    .topbar {
      position: sticky;
      top: 0;
      z-index: 30;
      backdrop-filter: blur(16px);
      background: rgba(252, 247, 242, 0.84);
      border-bottom: 1px solid rgba(62, 45, 35, 0.08);
    }

    .topbar-inner {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 20px;
      padding: 16px 0;
    }

    .brand {
      display: flex;
      align-items: center;
      gap: 16px;
    }

    .brand-mark {
      width: 178px;
      max-width: 42vw;
    }

    .nav-links {
      display: flex;
      align-items: center;
      gap: 24px;
      font-size: 14px;
      font-weight: 700;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: var(--muted);
    }

    .nav-links a:hover {
      color: var(--accent-dark);
    }

    .nav-cta {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 13px 20px;
      border-radius: 999px;
      background: linear-gradient(135deg, var(--accent), var(--accent-dark));
      color: #fff;
      font-size: 13px;
      font-weight: 800;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      box-shadow: 0 16px 30px rgba(177, 132, 88, 0.24);
    }

    .nav-cta-secondary {
      background: rgba(43, 91, 85, 0.1);
      color: var(--forest);
      box-shadow: none;
      border: 1px solid rgba(43, 91, 85, 0.18);
    }

    .hero {
      position: relative;
      z-index: 1;
      padding: 42px 0 36px;
    }

    .hero-grid {
      display: grid;
      grid-template-columns: 1.05fr 0.95fr;
      gap: 28px;
      align-items: stretch;
    }

    .hero-copy,
    .hero-visual {
      border: 1px solid rgba(255, 255, 255, 0.5);
      border-radius: var(--radius-xl);
      background: var(--surface);
      box-shadow: var(--shadow);
      backdrop-filter: blur(18px);
    }

    .hero-copy {
      position: relative;
      overflow: hidden;
      padding: 40px;
      background:
        linear-gradient(160deg, rgba(255, 255, 255, 0.88), rgba(251, 244, 236, 0.75)),
        var(--surface);
    }

    .hero-copy::after {
      content: "";
      position: absolute;
      right: -70px;
      bottom: -90px;
      width: 240px;
      height: 240px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(177, 132, 88, 0.18), rgba(177, 132, 88, 0));
      pointer-events: none;
    }

    .eyebrow {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      padding: 8px 14px;
      border-radius: 999px;
      background: rgba(177, 132, 88, 0.12);
      color: var(--accent-dark);
      font-size: 12px;
      font-weight: 800;
      letter-spacing: 0.14em;
      text-transform: uppercase;
    }

    .hero-copy h1,
    .section-copy h2,
    .booking-header h2,
    .contact-card h2 {
      margin: 18px 0 16px;
      font-family: "Libre Baskerville", serif;
      font-weight: 700;
      letter-spacing: -0.03em;
      color: var(--text);
    }

    .hero-copy h1 {
      font-size: clamp(2.7rem, 5vw, 4.7rem);
      line-height: 1.05;
    }

    .hero-copy p {
      max-width: 620px;
      margin: 0 0 28px;
      color: var(--muted);
      font-size: 17px;
      line-height: 1.9;
    }

    .hero-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 14px;
      margin-bottom: 28px;
    }

    .btn-soft,
    .btn-outline-soft {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      padding: 15px 22px;
      border-radius: 999px;
      font-size: 13px;
      font-weight: 800;
      letter-spacing: 0.08em;
      text-transform: uppercase;
    }

    .btn-soft {
      background: linear-gradient(135deg, var(--accent), var(--accent-dark));
      color: #fff;
      box-shadow: 0 16px 30px rgba(177, 132, 88, 0.24);
    }

    .btn-outline-soft {
      border: 1px solid var(--border);
      background: rgba(255, 255, 255, 0.72);
      color: var(--text);
    }

    .hero-stats {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 16px;
    }

    .hero-stat {
      padding: 18px;
      border-radius: 24px;
      background: #fff;
      box-shadow: var(--shadow-soft);
    }

    .hero-stat strong {
      display: block;
      margin-bottom: 6px;
      font-size: 28px;
      font-weight: 800;
      color: var(--text);
    }

    .hero-stat span {
      display: block;
      color: var(--muted);
      font-size: 13px;
      line-height: 1.6;
    }

    .hero-visual {
      position: relative;
      overflow: hidden;
      padding: 18px;
      display: grid;
      grid-template-columns: 1.2fr 0.8fr;
      gap: 16px;
      min-height: 640px;
      background:
        linear-gradient(180deg, rgba(255, 255, 255, 0.58), rgba(255, 255, 255, 0.3)),
        rgba(255, 255, 255, 0.76);
      transition: transform 0.35s ease;
      will-change: transform;
    }

    .hero-visual::before {
      content: "";
      position: absolute;
      inset: 14px;
      border-radius: 30px;
      background: linear-gradient(135deg, rgba(255, 255, 255, 0.18), rgba(177, 132, 88, 0.06));
      pointer-events: none;
    }

    .hero-main-image,
    .hero-stack img,
    .feature-image,
    .service-card img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      border-radius: 28px;
    }

    .hero-main-image {
      position: relative;
      z-index: 1;
      animation: floatSoft 9s ease-in-out infinite;
      will-change: transform;
    }

    .hero-stack img {
      position: relative;
      z-index: 1;
      transition: transform 0.45s ease, box-shadow 0.45s ease;
      will-change: transform;
    }

    .hero-stack img:nth-child(1) {
      animation: floatSoft 10s ease-in-out infinite;
    }

    .hero-stack img:nth-child(2) {
      animation: floatSoft 11s ease-in-out infinite reverse;
    }

    .hero-stack img:nth-child(3) {
      animation: floatSoft 12s ease-in-out infinite;
    }

    .hero-stack {
      display: grid;
      grid-template-rows: repeat(3, 1fr);
      gap: 16px;
    }

    .section {
      position: relative;
      z-index: 1;
      padding: 34px 0;
    }

    .section-grid {
      display: grid;
      grid-template-columns: 0.95fr 1.05fr;
      gap: 28px;
      align-items: center;
    }

    .section-copy p,
    .contact-card p {
      color: var(--muted);
      line-height: 1.9;
      font-size: 16px;
    }

    .section-copy h2,
    .booking-header h2,
    .contact-card h2 {
      font-size: clamp(2rem, 4vw, 3.4rem);
      line-height: 1.15;
    }

    .feature-list {
      display: grid;
      gap: 16px;
      margin-top: 24px;
    }

    .feature-item {
      padding: 20px 22px;
      border: 1px solid var(--border);
      border-radius: 24px;
      background: rgba(255, 255, 255, 0.76);
      box-shadow: var(--shadow-soft);
    }

    .feature-item strong {
      display: block;
      margin-bottom: 8px;
      font-size: 17px;
    }

    .feature-item span {
      color: var(--muted);
      line-height: 1.7;
    }

    .feature-image-wrap {
      padding: 18px;
      border: 1px solid rgba(255, 255, 255, 0.5);
      border-radius: var(--radius-xl);
      background: var(--surface);
      box-shadow: var(--shadow);
    }

    .services-shell {
      position: relative;
      overflow: hidden;
      padding: 40px;
      border: 1px solid rgba(255, 255, 255, 0.5);
      border-radius: var(--radius-xl);
      background: rgba(255, 255, 255, 0.74);
      box-shadow: var(--shadow);
      backdrop-filter: blur(16px);
    }

    .services-shell::before {
      content: "";
      position: absolute;
      top: -120px;
      right: -60px;
      width: 260px;
      height: 260px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(177, 132, 88, 0.16), rgba(177, 132, 88, 0));
      pointer-events: none;
    }

    .services-head {
      display: flex;
      align-items: end;
      justify-content: space-between;
      gap: 18px;
      margin-bottom: 26px;
    }

    .services-head p {
      max-width: 560px;
      color: var(--muted);
      line-height: 1.8;
    }

    .services-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 20px;
    }

    .service-card {
      overflow: hidden;
      border-radius: 28px;
      background: var(--surface-strong);
      box-shadow: var(--shadow-soft);
      transition: transform 0.45s ease, box-shadow 0.45s ease;
    }

    .service-card img {
      height: 290px;
      border-radius: 0;
      transition: transform 0.65s ease;
    }

    .service-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 24px 46px rgba(62, 45, 35, 0.14);
    }

    .service-card:hover img {
      transform: scale(1.06);
    }

    .service-card-body {
      padding: 22px;
    }

    .service-card-body h3 {
      margin: 0 0 10px;
      font-family: "Libre Baskerville", serif;
      font-size: 26px;
      line-height: 1.2;
    }

    .service-card-body p {
      margin: 0;
      color: var(--muted);
      line-height: 1.8;
    }

    .booking-wrap {
      display: grid;
      grid-template-columns: 0.82fr 1.18fr;
      gap: 28px;
    }

    .booking-aside,
    .booking-card,
    .contact-card {
      border: 1px solid rgba(255, 255, 255, 0.5);
      border-radius: var(--radius-xl);
      background: rgba(255, 255, 255, 0.78);
      box-shadow: var(--shadow);
      backdrop-filter: blur(16px);
    }

    .booking-aside {
      position: relative;
      overflow: hidden;
      padding: 34px;
      background: linear-gradient(180deg, rgba(43, 91, 85, 0.96), rgba(32, 63, 59, 0.96));
      color: #fff;
    }

    .booking-aside::after {
      content: "";
      position: absolute;
      right: -70px;
      bottom: -80px;
      width: 240px;
      height: 240px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(255, 255, 255, 0.14), rgba(255, 255, 255, 0));
      pointer-events: none;
    }

    .booking-aside h3 {
      margin: 20px 0 14px;
      font-family: "Libre Baskerville", serif;
      font-size: 38px;
      line-height: 1.2;
    }

    .booking-aside p,
    .booking-note {
      color: rgba(255, 255, 255, 0.82);
      line-height: 1.9;
    }

    .booking-points {
      display: grid;
      gap: 14px;
      margin-top: 28px;
    }

    .booking-point {
      padding: 16px 18px;
      border: 1px solid rgba(255, 255, 255, 0.14);
      border-radius: 22px;
      background: rgba(255, 255, 255, 0.08);
    }

    .booking-point strong {
      display: block;
      margin-bottom: 6px;
    }

    .booking-card {
      padding: 34px;
    }

    .booking-header p {
      margin: 0 0 22px;
      color: var(--muted);
      line-height: 1.8;
    }

    .form-label {
      margin-bottom: 8px;
      color: var(--text);
      font-size: 13px;
      font-weight: 800;
      letter-spacing: 0.06em;
      text-transform: uppercase;
    }

    .form-control,
    .form-select {
      min-height: 54px;
      border: 1px solid var(--border);
      border-radius: var(--radius-md);
      background: rgba(255, 255, 255, 0.92);
      box-shadow: none;
      color: var(--text);
      padding: 14px 16px;
    }

    .form-control:focus,
    .form-select:focus {
      border-color: rgba(177, 132, 88, 0.5);
      box-shadow: 0 0 0 4px rgba(177, 132, 88, 0.12);
    }

    .select2-container {
      width: 100% !important;
    }

    .select2-container--default .select2-selection--multiple {
      min-height: 54px;
      border: 1px solid var(--border);
      border-radius: var(--radius-md) !important;
      background: rgba(255, 255, 255, 0.92);
      padding: 8px 12px;
    }

    .select2-container--default .select2-selection--multiple .select2-selection__rendered {
      display: flex;
      flex-wrap: wrap;
      gap: 6px;
      padding: 0 !important;
    }

    .select2-container--default .select2-selection--multiple .select2-selection__choice {
      margin-top: 0;
      border: none;
      border-radius: 999px;
      background: rgba(177, 132, 88, 0.14);
      color: var(--accent-dark);
      padding: 6px 10px;
    }

    .summary-card {
      margin-top: 6px;
      padding: 24px;
      border: 1px solid var(--border);
      border-radius: 28px;
      background: linear-gradient(145deg, rgba(249, 243, 236, 0.92), rgba(255, 255, 255, 0.92));
      box-shadow: var(--shadow-soft);
    }

    .summary-card h3 {
      margin: 0 0 8px;
      font-family: "Libre Baskerville", serif;
      font-size: 28px;
    }

    .summary-card p {
      margin: 0 0 18px;
      color: var(--muted);
      line-height: 1.7;
    }

    .summary-line {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 20px;
      padding: 12px 0;
      border-bottom: 1px solid rgba(62, 45, 35, 0.08);
    }

    .summary-line:last-child {
      border-bottom: 0;
    }

    .summary-line span {
      color: var(--muted);
      font-weight: 600;
    }

    .summary-line input {
      width: 120px;
      min-height: auto;
      padding: 0;
      border: 0;
      background: transparent;
      font-weight: 800;
      text-align: right;
      box-shadow: none;
    }

    .summary-total {
      margin-top: 8px;
      padding-top: 18px;
      border-top: 1px solid rgba(62, 45, 35, 0.12);
    }

    .summary-total span,
    .summary-total input {
      color: var(--text);
      font-size: 18px;
    }

    .btn-book {
      width: 100%;
      margin-top: 10px;
      padding: 16px 20px;
      border: 0;
      border-radius: 999px;
      background: linear-gradient(135deg, var(--accent), var(--accent-dark));
      color: #fff;
      font-size: 14px;
      font-weight: 800;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      box-shadow: 0 18px 36px rgba(177, 132, 88, 0.24);
    }

    .form-note {
      margin-top: 14px;
      color: var(--muted);
      font-size: 13px;
      text-align: center;
    }

    .contact-grid {
      display: grid;
      grid-template-columns: 0.95fr 1.05fr;
      gap: 28px;
    }

    .parallax-band {
      position: relative;
      z-index: 1;
      min-height: 72vh;
      margin: 28px 0;
      overflow: hidden;
      background: #201814;
    }

    .parallax-media {
      position: absolute;
      inset: 0;
      background:
        linear-gradient(90deg, rgba(25, 18, 14, 0.72), rgba(25, 18, 14, 0.28)),
        url("assets/images/landing/about.jpg") center center / cover no-repeat;
      transform: scale(1.12);
      will-change: transform;
    }

    .parallax-band::after {
      content: "";
      position: absolute;
      inset: 0;
      background:
        linear-gradient(180deg, rgba(15, 10, 8, 0.18), rgba(15, 10, 8, 0.38)),
        radial-gradient(circle at 75% 35%, rgba(177, 132, 88, 0.18), rgba(177, 132, 88, 0));
    }

    .parallax-content {
      position: relative;
      z-index: 1;
      display: flex;
      align-items: center;
      min-height: 72vh;
      color: #fff;
    }

    .parallax-card {
      max-width: 640px;
      padding: 34px;
      border: 1px solid rgba(255, 255, 255, 0.2);
      border-radius: 32px;
      background: rgba(255, 255, 255, 0.08);
      backdrop-filter: blur(10px);
      box-shadow: 0 22px 48px rgba(0, 0, 0, 0.18);
    }

    .parallax-card h2 {
      margin: 16px 0 14px;
      font-family: "Libre Baskerville", serif;
      font-size: clamp(2.2rem, 4vw, 4rem);
      line-height: 1.15;
    }

    .parallax-card p {
      margin: 0 0 22px;
      color: rgba(255, 255, 255, 0.84);
      line-height: 1.85;
      font-size: 16px;
    }

    .parallax-card .eyebrow {
      background: rgba(255, 255, 255, 0.12);
      color: #fff;
    }

    .contact-card {
      padding: 34px;
    }

    .contact-list {
      display: grid;
      gap: 16px;
      margin-top: 24px;
    }

    .contact-item {
      padding: 18px 20px;
      border: 1px solid var(--border);
      border-radius: 22px;
      background: rgba(255, 255, 255, 0.74);
    }

    .contact-item strong {
      display: block;
      margin-bottom: 6px;
      font-size: 16px;
    }

    .map-card {
      min-height: 100%;
      border-radius: var(--radius-xl);
      overflow: hidden;
      box-shadow: var(--shadow);
    }

    .map-card iframe {
      width: 100%;
      min-height: 100%;
      border: 0;
    }

    .site-footer {
      padding: 24px 0 44px;
      color: var(--muted);
      font-size: 14px;
      text-align: center;
    }

    .error {
      display: block;
      margin-top: 8px;
      color: #c2574f !important;
      font-size: 13px;
      font-weight: 700;
    }

    [data-reveal] {
      opacity: 0;
      transform: translateY(28px) scale(0.98);
      transition: opacity 0.8s ease, transform 0.8s ease;
      transition-delay: var(--delay, 0ms);
    }

    [data-reveal].is-visible {
      opacity: 1;
      transform: translateY(0) scale(1);
    }

    [data-reveal="left"] {
      transform: translateX(-36px);
    }

    [data-reveal="right"] {
      transform: translateX(36px);
    }

    [data-reveal="left"].is-visible,
    [data-reveal="right"].is-visible {
      transform: translateX(0);
    }

    @keyframes floatSoft {
      0%, 100% {
        transform: translate3d(0, 0, 0);
      }
      50% {
        transform: translate3d(0, -10px, 0);
      }
    }

    @keyframes floatBlob {
      0%, 100% {
        transform: translate3d(0, 0, 0) scale(1);
      }
      50% {
        transform: translate3d(18px, -22px, 0) scale(1.08);
      }
    }

    .nav-toggle {
      display: none;
      border: none;
      background: none;
      font-size: 24px;
      color: var(--text);
      cursor: pointer;
      padding: 8px;
    }

    .nav-drawer {
      position: fixed;
      top: 0;
      right: 0;
      width: min(88vw, 320px);
      height: 100vh;
      z-index: 40;
      background: var(--surface-strong);
      box-shadow: var(--shadow);
      transform: translateX(100%);
      transition: transform 0.3s ease;
      display: flex;
      flex-direction: column;
      padding: 24px 20px;
    }

    .nav-drawer.is-open {
      transform: translateX(0);
    }

    .nav-drawer-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 32px;
    }

    .nav-drawer-links {
      display: flex;
      flex-direction: column;
      gap: 6px;
    }

    .nav-drawer-links a {
      display: block;
      padding: 14px 16px;
      border-radius: 14px;
      font-weight: 700;
      font-size: 15px;
      color: var(--text);
    }

    .nav-drawer-links a:hover {
      background: rgba(177, 132, 88, 0.1);
    }

    .nav-drawer-links .nav-cta {
      margin-top: 12px;
      text-align: center;
    }

    .nav-overlay {
      position: fixed;
      inset: 0;
      z-index: 35;
      background: rgba(0, 0, 0, 0.5);
      opacity: 0;
      visibility: hidden;
      transition: opacity 0.3s ease, visibility 0.3s ease;
    }

    .nav-overlay.is-visible {
      opacity: 1;
      visibility: visible;
    }

    @media (max-width: 1100px) {
      .hero-grid,
      .section-grid,
      .booking-wrap,
      .contact-grid {
        grid-template-columns: 1fr;
      }

      .services-grid {
        grid-template-columns: 1fr;
      }

      .hero-visual {
        min-height: 520px;
      }

      .parallax-band,
      .parallax-content {
        min-height: 56vh;
      }
    }

    @media (max-width: 860px) {
      .topbar-inner {
        gap: 12px;
        padding: 12px 0;
      }

      .brand-mark {
        width: 130px;
      }

      .nav-links {
        gap: 14px;
        font-size: 11px;
      }

      .nav-cta {
        padding: 10px 14px;
        font-size: 11px;
      }

      .hero-copy h1 {
        font-size: clamp(1.8rem, 4vw, 2.8rem);
        margin: 12px 0 10px;
      }

      .hero-copy p,
      .section-copy p,
      .contact-card p {
        font-size: 14px;
      }

      .section-copy h2,
      .booking-header h2,
      .contact-card h2 {
        font-size: clamp(1.4rem, 3vw, 2rem);
      }

      .hero-stat strong {
        font-size: 22px;
      }

      .hero-stat span {
        font-size: 12px;
      }

      .service-card-body h3 {
        font-size: 20px;
      }

      .service-card-body p {
        font-size: 13px;
      }

      .booking-aside h3 {
        font-size: 28px;
      }

      .hero-stat {
        border-radius: 18px;
        padding: 14px;
      }

      .hero-stats {
        grid-template-columns: 1fr;
      }

      .feature-item {
        padding: 14px 16px;
      }

      .feature-item strong {
        font-size: 15px;
      }

      .feature-item span {
        font-size: 13px;
      }

      .summary-card h3 {
        font-size: 22px;
      }

      .hero-copy,
      .services-shell,
      .booking-card,
      .booking-aside,
      .contact-card {
        padding: 24px;
      }
    }

    @media (max-width: 500px) {
      .nav-toggle {
        display: block;
      }

      .nav-links,
      .topbar-inner > .nav-cta {
        display: none !important;
      }

      .nav-drawer-links .nav-cta {
        display: flex !important;
      }

      .topbar-inner {
        gap: 8px;
        padding: 8px 0;
      }

      .brand-mark {
        width: 100px;
      }

      .hero-copy h1 {
        font-size: 1.6rem;
      }

      .hero-copy p,
      .section-copy p,
      .contact-card p {
        font-size: 13px;
      }

      .section-copy h2,
      .booking-header h2,
      .contact-card h2 {
        font-size: 1.3rem;
      }

      .hero-copy,
      .services-shell,
      .booking-card,
      .booking-aside,
      .contact-card {
        padding: 16px;
      }

      .hero-stat strong {
        font-size: 18px;
      }

      .hero-stat span {
        font-size: 11px;
      }

      .feature-item {
        padding: 12px 14px;
      }

      .feature-item strong {
        font-size: 14px;
      }

      .feature-item span {
        font-size: 12px;
      }

      .service-card-body h3 {
        font-size: 18px;
      }

      .service-card-body p {
        font-size: 12px;
      }

      .service-card-body {
        padding: 14px;
      }

      .services-head p {
        font-size: 13px;
      }

      .booking-aside h3 {
        font-size: 24px;
      }

      .summary-card {
        padding: 16px;
      }

      .summary-card h3 {
        font-size: 20px;
      }

      .summary-card p {
        font-size: 13px;
      }

      .form-label {
        font-size: 11px;
      }

      .form-control,
      .form-select {
        min-height: 44px;
        padding: 10px 12px;
        font-size: 13px;
      }

      .hero-grid {
        gap: 16px;
      }

      .section {
        padding: 20px 0;
      }

      .services-grid {
        gap: 14px;
      }

      .service-card img {
        height: 200px;
      }
    }

    @media (max-width: 640px) {
      .container-shell {
        width: min(100% - 20px, 1200px);
      }

      .hero-copy,
      .services-shell,
      .booking-card,
      .booking-aside,
      .contact-card {
        padding: 20px;
      }

      .hero-visual {
        grid-template-columns: 1fr;
        min-height: auto;
      }

      .hero-main-image {
        min-height: 260px;
      }

      .hero-stack {
        grid-template-columns: repeat(3, 1fr);
        grid-template-rows: auto;
      }

      .services-grid {
        gap: 16px;
      }

      .parallax-card {
        padding: 20px;
      }

      .parallax-card h2 {
        font-size: 1.6rem;
      }

      .parallax-card p {
        font-size: 14px;
      }
    }
  </style>
</head>

<body>
  <div class="page-shell">
    <header class="topbar">
      <div class="container-shell topbar-inner">
        <a class="brand" href="#home" data-reveal="left">
          <img class="brand-mark" src="assets/images/landing/logo.png"
            alt="Marie Noelle Spa and Salon">
        </a>

        <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">
          <i class="fa fa-bars"></i>
        </button>

        <nav class="nav-links" data-reveal="right">
          <a href="#about">About</a>
          <a href="#services">Services</a>
          <a href="#booking">Book</a>
          <a href="#contact">Contact</a>
          <a href="staff/">Staff Login</a>
        </nav>

        <a class="nav-cta" href="#booking">Book Your Session</a>
      </div>
    </header>

    <div class="nav-drawer" id="navDrawer">
      <div class="nav-drawer-header">
        <img class="brand-mark" src="assets/images/landing/logo.png" alt="Marie Noelle Spa and Salon">
        <button class="nav-toggle is-close" id="navClose" aria-label="Close navigation">
          <i class="fa fa-times"></i>
        </button>
      </div>
      <nav class="nav-drawer-links">
        <a href="#about">About</a>
        <a href="#services">Services</a>
        <a href="#booking">Book</a>
        <a href="#contact">Contact</a>
        <a href="staff/">Staff Login</a>
        <a class="nav-cta" href="#booking">Book Your Session</a>
      </nav>
    </div>

    <div class="nav-overlay" id="navOverlay"></div>

    <main>
      <section class="hero" id="home">
        <div class="container-shell hero-grid">
          <div class="hero-copy" data-reveal="left">
            <span class="eyebrow">Welcome to Marie Noelle Spa and Salon</span>
            <h1>Discover serenity at Marie Noelle Spa and Salon.</h1>
            <p>Step into a world of tranquility where self-care and self love meets luxury. From soothing massages and radiant facial treatments to exquisite nail care and beauty rituals, your journey to relaxation begins here.</p>

            <div class="hero-actions">
              <a class="btn-soft" href="#booking">Book Your Session</a>
              <a class="btn-outline-soft" href="#about">Get To Know Us</a>
              <a class="btn-outline-soft" href="staff/">Staff Portal</a>
            </div>

            <div class="hero-stats">
              <div class="hero-stat">
                <strong><?php echo $serviceCount; ?>+</strong>
                <span>Services and spa treatments available to book.</span>
              </div>
              <div class="hero-stat">
                <strong><?php echo $taxCount; ?></strong>
                <span>Pricing and tax rules included in your total instantly.</span>
              </div>
              <div class="hero-stat">
                <strong>09-17</strong>
                <span>Open daily for wellness, beauty, and relaxation sessions.</span>
              </div>
            </div>
          </div>

          <div class="hero-visual" data-reveal="right" data-parallax-wrap>
            <img class="hero-main-image"
              src="assets/images/landing/hero-main.jpg"
              alt="Marie Noelle Spa and Salon interior">
            <div class="hero-stack">
              <img src="assets/images/landing/hero-side-1.jpg"
                alt="Spa treatment room">
              <img src="assets/images/landing/hero-side-2.jpg"
                alt="Massage experience">
              <img src="assets/images/landing/hero-side-3.jpg"
                alt="Nail care experience">
            </div>
          </div>
        </div>
      </section>

      <section class="section" id="about">
        <div class="container-shell section-grid">
          <div class="section-copy" data-reveal="left">
            <span class="eyebrow">A Haven Where Time Stands Still</span>
            <h2>Unwind, rejuvenate, and glow in a serene world designed for indulgence.</h2>
            <p>Marie Noelle Spa and Salon is built around calm luxury. Inspired by the reference site, this landing page keeps the same warm, elegant spa identity while also preserving your booking flow locally. Guests can explore signature experiences, understand the atmosphere, and move directly into booking without friction.</p>

            <div class="feature-list">
              <div class="feature-item">
                <strong>Luxury spa atmosphere</strong>
                <span>A refined environment focused on comfort, balance, and intentional care from the moment a guest arrives.</span>
              </div>
              <div class="feature-item">
                <strong>Beauty and wellness under one roof</strong>
                <span>Massages, facials, nails, and beauty sessions are presented together with a polished high-end feel.</span>
              </div>
              <div class="feature-item">
                <strong>Direct path to appointments</strong>
                <span>The landing page leads naturally into the booking section so visitors can take action immediately.</span>
              </div>
            </div>
          </div>

          <div class="feature-image-wrap" data-reveal="right">
            <img class="feature-image"
              src="assets/images/landing/about.jpg"
              alt="Marie Noelle Spa and Salon experience">
          </div>
        </div>
      </section>

      <section class="parallax-band">
        <div class="parallax-media" data-parallax-bg></div>
        <div class="container-shell parallax-content">
          <div class="parallax-card" data-reveal="left">
            <span class="eyebrow">A Luxury Retreat</span>
            <h2>Where beauty rituals and deep calm meet modern elegance.</h2>
            <p>We’ve turned the landing page into a more immersive spa experience with atmospheric imagery, floating motion, and a dedicated parallax moment that makes the page feel more premium while keeping your booking flow intact.</p>
            <a class="btn-soft" href="#booking">Reserve Your Session</a>
          </div>
        </div>
      </section>

      <section class="section" id="services">
        <div class="container-shell services-shell" data-reveal>
          <div class="services-head">
            <div>
              <span class="eyebrow">Signature Experiences</span>
              <h2 style="margin-bottom:12px;">Relaxation, beauty, and expert care in every visit.</h2>
            </div>
            <p>These featured blocks mirror the original site’s focus on massages, facials, and nail care, giving the homepage the same premium service storytelling before the booking form.</p>
          </div>

          <div class="services-grid">
            <article class="service-card" data-reveal style="--delay: 0ms;">
              <img src="assets/images/landing/hero-side-2.jpg"
                alt="Signature massage experience">
              <div class="service-card-body">
                <h3>Signature Massage Experience</h3>
                <p>Unwind with the power of touch through expertly delivered massage therapies designed to ease tension, improve circulation, and promote deep relaxation.</p>
              </div>
            </article>

            <article class="service-card" data-reveal style="--delay: 100ms;">
              <img src="assets/images/landing/hero-side-1.jpg"
                alt="Radiant facial glow">
              <div class="service-card-body">
                <h3>Radiant Facial Glow</h3>
                <p>Experience ultimate facial treatments tailored for hydration, glow, and skin renewal with expert skincare solutions and a luxurious finish.</p>
              </div>
            </article>

            <article class="service-card" data-reveal style="--delay: 200ms;">
              <img src="assets/images/landing/hero-side-3.jpg"
                alt="Glamorous nail retreat">
              <div class="service-card-body">
                <h3>Glamorous Nail Retreat</h3>
                <p>Pamper your hands and feet with professional nail care that feels polished, elegant, and restorative from start to finish.</p>
              </div>
            </article>
          </div>
        </div>
      </section>

      <section class="section" id="booking">
        <div class="container-shell booking-wrap">
          <aside class="booking-aside" data-reveal="left">
            <span class="eyebrow" style="background:rgba(255,255,255,0.12);color:#fff;">Reserve Your Experience</span>
            <h3>Reserve your exclusive spa experience today.</h3>
              <p class="booking-note">Select your services, review the total, and book your appointment with ease.</p>

            <div class="booking-points">
              <div class="booking-point">
                <strong>Choose multiple services</strong>
                <span>Select treatments and let the price update automatically.</span>
              </div>
              <div class="booking-point">
                <strong>Review totals clearly</strong>
                <span>Base amount, tax, and final amount are shown before checkout.</span>
              </div>
              <div class="booking-point">
                <strong>Instant confirmation</strong>
                <span>Your appointment is booked immediately after submission.</span>
              </div>
            </div>
          </aside>

          <div class="booking-card" data-reveal="right">
            <div class="booking-header">
              <span class="eyebrow">Book Your Appointment</span>
              <h2>Schedule your next visit.</h2>
              <p>Fill in your details, select your preferred services, and submit to book your appointment.</p>
            </div>

            <form action="booking.php" method="post" id="add_slider">
              <div class="row g-4">
                <div class="col-md-6">
                  <label for="fullname" class="form-label">Full Name</label>
                  <input type="text" id="fullname" name="name" class="form-control" placeholder="Enter your full name">
                </div>

                <input type="hidden" name="discount_type" id="discount_type" value="">
                <input type="hidden" name="discount_value" id="discount_value" value="0">

                <div class="col-md-6">
                  <label for="email" class="form-label">Email Address</label>
                  <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email">
                </div>

                <div class="col-md-6">
                  <label for="phone" class="form-label">Phone Number</label>
                  <input type="tel" id="phone" name="phone" class="form-control" placeholder="Enter your phone number">
                </div>

                <div class="col-md-6">
                  <label for="date" class="form-label">Appointment Date</label>
                  <input type="date" id="date" name="apt_date" class="form-control">
                </div>

                <div class="col-md-6">
                  <label for="time" class="form-label">Appointment Time</label>
                  <input type="time" id="time" name="apt_time" class="form-control">
                </div>

                <div class="col-md-6">
                  <label for="services-select" class="form-label">Services</label>
                  <select id="services-select" name="serv_id[]" class="form-select select2" multiple="multiple">
                    <option value="">Select a service</option>
                    <?php
                    $retr = mysqli_query($con, "select * from tblservices");
                    while ($rowr = mysqli_fetch_array($retr)) { ?>
                      <option value="<?php echo $rowr['ID']; ?>" data-cost="<?php echo $rowr['Cost']; ?>">
                        <?php echo $rowr['ServiceName']; ?>
                      </option>
                    <?php } ?>
                  </select>
                </div>

                  <div class="col-12">
                    <div class="summary-card">
                      <h3>Pricing Summary</h3>
                      <p>Your pricing updates automatically based on selected services and configured taxes.</p>

                      <div class="summary-line">
                        <span>Base price</span>
                        <input type="text" id="total" name="total" value="" readonly style="text-align:right;">
                      </div>

                      <?php
                      $ret = mysqli_query($con, "select * from tbl_tax");
                      while ($row = mysqli_fetch_array($ret)) { ?>
                        <div class="summary-line">
                          <span><?php echo $row['name']; ?> (%)</span>
                          <input type="text" value="<?php echo $row['value']; ?>" class="tax_value" readonly>
                        </div>
                      <?php } ?>

                      <div class="summary-line" id="discount_summary_line" style="display:none;">
                        <span>Discount</span>
                        <input type="text" id="discount_display" value="" readonly style="text-align:right;color:#c2574f;">
                      </div>

                      <div class="summary-line" id="discount_type_wrapper" style="border-bottom:none; padding-bottom:4px;">
                        <span style="font-size:13px; font-weight:600;">Have a discount?</span>
                        <div style="display:flex; gap:10px; align-items:center;">
                          <select id="discount_type_select" style="padding:6px 10px; border-radius:10px; border:1px solid var(--border); font-size:13px; background:transparent;">
                            <option value="">None</option>
                            <option value="percentage">%</option>
                            <option value="fixed">GH₵</option>
                          </select>
                          <input type="number" id="discount_value_input" min="0" step="0.01" value="" placeholder="0" style="width:90px; padding:6px 10px; border-radius:10px; border:1px solid var(--border); font-size:13px;">
                        </div>
                      </div>

                      <div class="summary-line summary-total">
                        <span>Total with tax (GH₵)</span>
                        <input type="text" id="grand_total" name="grand_total" value="" readonly style="text-align:right;">
                      </div>
                    </div>
                  </div>

                <div class="col-12">
                  <button type="submit" class="btn-book">Book Appointment</button>
                  <p class="form-note">Your appointment will be booked immediately.</p>
                </div>
              </div>
            </form>
          </div>
        </div>
      </section>

      <section class="section" id="contact">
        <div class="container-shell contact-grid">
          <div class="contact-card" data-reveal="left">
            <span class="eyebrow">Contact</span>
            <h2>Visit or contact Marie Noelle Spa and Salon.</h2>
            <p>These contact details come directly from the live reference site so the landing page reflects the same brand identity and business information.</p>

            <div class="contact-list">
              <div class="contact-item">
                <strong>Address</strong>
                <span>Osu-40 Salem Road, Kuku Hill, Osu-Accra, Greater Accra Region</span>
              </div>
              <div class="contact-item">
                <strong>Email</strong>
                <span><a href="mailto:info@marienoellespas.com">info@marienoellespas.com</a></span>
              </div>
              <div class="contact-item">
                <strong>Phone</strong>
                <span><a href="tel:0591466455">0591466455</a></span>
              </div>
              <div class="contact-item">
                <strong>Hours</strong>
                <span>Monday to Sunday, 09:00 - 17:00</span>
              </div>
            </div>
          </div>

          <div class="map-card" data-reveal="right">
            <iframe
              src="https://www.google.com/maps?q=Osu-40%20Salem%20Road%2C%20Kuku%20Hill%2C%20Osu-Accra&output=embed"
              loading="lazy" referrerpolicy="no-referrer-when-downgrade"
              title="Map showing Marie Noelle Spa and Salon location"></iframe>
          </div>
        </div>
      </section>
    </main>

    <footer class="site-footer">
      <div class="container-shell">Marie Noelle Spa and Salon.</div>
    </footer>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.20.0/jquery.validate.min.js"
    integrity="sha512-WMEKGZ7L5LWgaPeJtw9MBM4i5w5OSBlSjTjCtSnvFJGSVD26gE5+Td12qN5pvWXhuWaWcVwF++F7aqu9cvqP0A=="
    crossorigin="anonymous" referrerpolicy="no-referrer"></script>

  <script>
    $(document).ready(function () {
      $('.select2').select2({
        placeholder: 'Select services',
        width: '100%'
      });
    });
  </script>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      var revealItems = document.querySelectorAll('[data-reveal]');
      var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-visible');
            observer.unobserve(entry.target);
          }
        });
      }, { threshold: 0.18 });

      revealItems.forEach(function (item) {
        observer.observe(item);
      });

      var parallaxBg = document.querySelector('[data-parallax-bg]');
      var heroVisual = document.querySelector('[data-parallax-wrap]');

      function updateParallax() {
        var scrollY = window.scrollY || window.pageYOffset;

        if (parallaxBg) {
          parallaxBg.style.transform = 'translate3d(0,' + (scrollY * 0.14) + 'px,0) scale(1.12)';
        }

        if (heroVisual && window.innerWidth > 860) {
          heroVisual.style.transform = 'translate3d(0,' + (scrollY * 0.04) + 'px,0)';
        } else if (heroVisual) {
          heroVisual.style.transform = 'none';
        }
      }

      updateParallax();
      window.addEventListener('scroll', updateParallax, { passive: true });
    });
  </script>

  <script>
    $(document).ready(function () {
      function updatePricing() {
        let total = 0;
        $('#services-select option:selected').each(function () {
          let cost = parseFloat($(this).data('cost')) || 0;
          total += cost;
        });
        $('#total').val(total.toFixed(2));

        let taxPercent = 0;
        $('.tax_value').each(function () {
          let val = parseFloat($(this).val()) || 0;
          taxPercent += val;
        });

        let taxAmount = total * taxPercent / 100;
        let preDiscountTotal = total + taxAmount;

        let discType = $('#discount_type_select').val();
        let discVal = parseFloat($('#discount_value_input').val()) || 0;
        let discAmount = 0;

        if (discType === 'percentage' && discVal > 0) {
          discAmount = preDiscountTotal * Math.min(discVal, 100) / 100;
        } else if (discType === 'fixed' && discVal > 0) {
          discAmount = Math.min(discVal, preDiscountTotal);
        }

        if (discAmount > 0) {
          $('#discount_summary_line').show();
          let discLabel = discType === 'percentage' ? discVal + '%' : 'GH₵ ' + discVal.toFixed(2);
          $('#discount_display').val('-' + discAmount.toFixed(2));
          $('#discount_type').val(discType);
          $('#discount_value').val(discVal);
        } else {
          $('#discount_summary_line').hide();
          $('#discount_display').val('');
          $('#discount_type').val('');
          $('#discount_value').val(0);
        }

        let finalAmount = preDiscountTotal - discAmount;
        $('#grand_total').val(finalAmount.toFixed(2));
      }

      $('#services-select').on('change', updatePricing);
      $('#discount_type_select').on('change', updatePricing);
      $('#discount_value_input').on('input', updatePricing);
    });
  </script>

  <script>
    $(document).ready(function () {
      jQuery.validator.addMethod("noDigits", function (value, element) {
        return this.optional(element) || !/\d/.test(value);
      }, "Please enter a value without digits.");

      jQuery.validator.addMethod("noSpacesOnly", function (value, element) {
        return value.trim() !== '';
      }, "Please enter a non-empty value");

      $('#add_slider').validate({
        rules: {
          name: {
            required: true
          },
          apt_time: {
            required: true
          },
          email: {
            required: true,
            email: true
          },
          apt_date: {
            required: true
          },
          serv_id: {
            required: true
          },
          phone: {
            required: true,
            noSpacesOnly: true,
            digits: true,
            minlength: 10,
            maxlength: 10
          }
        },
        messages: {
          name: {
            required: "Please enter a name."
          },
          serv_id: {
            required: "Please select at least one service."
          },
          email: {
            required: "Please enter an email."
          },
          phone: {
            required: "Please enter a phone number."
          },
          apt_date: {
            required: "Please enter a date."
          },
          apt_time: {
            required: "Please enter a time."
          }
        },
        submitHandler: function (form) {
          form.submit();
        }
      });
    });
  </script>

  <script>
    (function () {
      var toggle = document.getElementById('navToggle');
      var close = document.getElementById('navClose');
      var drawer = document.getElementById('navDrawer');
      var overlay = document.getElementById('navOverlay');

      function openDrawer() {
        drawer.classList.add('is-open');
        overlay.classList.add('is-visible');
        document.body.style.overflow = 'hidden';
      }

      function closeDrawer() {
        drawer.classList.remove('is-open');
        overlay.classList.remove('is-visible');
        document.body.style.overflow = '';
      }

      if (toggle) toggle.addEventListener('click', openDrawer);
      if (close) close.addEventListener('click', closeDrawer);
      if (overlay) overlay.addEventListener('click', closeDrawer);

      if (drawer) {
        drawer.querySelectorAll('a').forEach(function (link) {
          link.addEventListener('click', closeDrawer);
        });
      }
    })();
  </script>

  
</body>

</html>
