<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>{{ $title }}</title>
        <link rel="icon" type="image/png" href="{{ asset('assets/images/favicon.png') }}">
        <link rel="apple-touch-icon" href="{{ asset('assets/images/favicon.png') }}">
        <style>
            body {
                font-family: DejaVu Sans, Arial, sans-serif;
                font-size: 11px;
                color: #111827;
            }
            h1 {
                font-size: 16px;
                margin-bottom: 12px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
            }
            th, td {
                border: 1px solid #e5e7eb;
                padding: 6px 8px;
                vertical-align: top;
            }
            th {
                background: #f3f4f6;
                text-align: left;
            }
        </style>
    </head>
    <body>
        <h1>{{ $title }}</h1>
        <table>
            <thead>
                <tr>
                    @foreach ($headers as $header)
                        <th>{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        @foreach ($row as $cell)
                            <td>{{ $cell }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($headers) }}">No data available.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </body>
</html>
