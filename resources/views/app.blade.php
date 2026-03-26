<!DOCTYPE html>
<html lang="en" data-theme="default" >
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title inertia>DOST-IX</title>
        <meta name="description" content="Laboratory Information Management System">
        <meta name="keywords" content="DOST, LIMS, ONELAB">
        <meta name="author" content="Krad">
        <meta property="og:title" content="LIMS - Laboratory Information Management System">
        <meta property="og:description" content="Laboratory Management System">
        <meta property="og:image" content="URL to the template's logo or featured image">
        <meta property="og:url" content="URL to the template's webpage">
        <meta name="twitter:card" content="summary_large_image">
        <link rel="shortcut icon" href="{{ URL::asset('images/favicon.ico') }}">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/1.8.349/pdf.min.js"></script>
        @vite(['resources/js/app.js'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
