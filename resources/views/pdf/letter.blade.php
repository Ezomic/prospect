<!DOCTYPE html>
<html lang="{{ $letter->language->value }}">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 120px 90px; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11pt;
            line-height: 1.6;
            color: #1a1a1a;
        }
        .sender {
            text-align: right;
            font-size: 10pt;
            color: #444;
            margin-bottom: 40px;
        }
        .sender strong { color: #1a1a1a; }
        .meta { margin-bottom: 30px; }
        .recipient { margin-bottom: 6px; }
        .date { color: #444; }
        .subject { font-weight: bold; margin-bottom: 24px; }
        .body { white-space: pre-line; }
    </style>
</head>
<body>
    <div class="sender">
        <strong>Robbin Thijssen</strong><br>
        Thijssen Software<br>
        info@thijssensoftware.nl
    </div>

    <div class="meta">
        <div class="recipient">
            {{ $letter->company->name }}@if ($letter->company->contact_name)<br>T.a.v. {{ $letter->company->contact_name }}@endif
            @if ($letter->company->city)<br>{{ $letter->company->city }}@endif
        </div>
        <div class="date">{{ ($letter->generated_at ?? now())->locale($letter->language->locale())->isoFormat($letter->language->dateFormat()) }}</div>
    </div>

    <div class="subject">{{ $letter->subject }}</div>

    <div class="body">{{ $letter->body }}</div>
</body>
</html>
