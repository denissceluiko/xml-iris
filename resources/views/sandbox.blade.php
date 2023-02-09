<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Sandbox page</title>
    </head>
    <body>
        <pre>{{ print_r($result) }}</pre>
    </body>
</html>
