@props(['document', 'project'])
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $document['title'] }} · {{ $project->name }} · EnerSim</title>
    <meta name="description" content="{{ $document['description'] }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite('resources/css/report.less')
</head>
<body class="report-document report-document-{{ $document['type'] }}">
    <x-ad-placeholders />
    {{ $slot }}
</body>
</html>
