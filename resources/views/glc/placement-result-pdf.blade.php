<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Placement Test Result</title>
    <style>
        @page { margin: 48px 56px; }
        body { font-family: DejaVu Sans, Helvetica, Arial, sans-serif; color: #1e293b; font-size: 12px; margin: 0; print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        .logo-block { width: 64px; height: 64px; background-color: #059669; color: #ffffff; text-align: center; font-weight: bold; font-size: 22px; line-height: 64px; border-radius: 8px; }
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .header-table td { vertical-align: middle; border: none; padding: 0; }
        .center-name { font-size: 14px; font-weight: bold; color: #059669; }
        .center-sub { font-size: 10px; color: #64748b; }
        h1 { font-size: 22px; margin: 18px 0 4px; color: #0f172a; }
        .meta { color: #475569; font-size: 12px; margin-bottom: 18px; }
        .meta strong { color: #0f172a; }
        table.skills { width: 100%; border-collapse: collapse; margin: 12px 0 4px; }
        table.skills th, table.skills td { border: 1px solid #cbd5e1; padding: 8px 10px; text-align: left; font-size: 12px; }
        table.skills th { background-color: #f1f5f9; font-weight: bold; color: #334155; }
        .overall { margin: 14px 0 20px; padding: 12px 16px; background-color: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 6px; }
        .overall .label { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #047857; }
        .overall .value { font-size: 18px; font-weight: bold; color: #065f46; }
        h2 { font-size: 13px; color: #0f172a; margin: 16px 0 4px; }
        p.narrative { margin: 0 0 6px; line-height: 1.55; color: #334155; }
        .footer { margin-top: 32px; padding-top: 12px; border-top: 1px solid #e2e8f0; font-size: 10px; color: #94a3b8; line-height: 1.6; }
    </style>
</head>
<body>
    {{-- GLC logo placeholder until branding assets arrive --}}
    <table class="header-table">
        <tr>
            <td style="width: 76px;">
                <div class="logo-block">GLC</div>
            </td>
            <td>
                <div class="center-name">Greats Language Center</div>
                <div class="center-sub">English Language Placement — Kuala Lumpur</div>
            </td>
        </tr>
    </table>

    <h1>Placement Test Result</h1>

    <div class="meta">
        Candidate: <strong>{{ $candidateName }}</strong><br>
        Test date: <strong>{{ $testDate }}</strong>
    </div>

    <table class="skills">
        <thead>
            <tr>
                <th>Skill</th>
                <th>GLC Level</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($skillLevels as $row)
                <tr>
                    <td>{{ $row['skill'] }}</td>
                    <td>{{ $row['level'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="overall">
        <div class="label">Overall GLC Level</div>
        <div class="value">{{ $overallLevel }}</div>
    </div>

    @foreach ($narrative as $heading => $text)
        @if ($text)
            <h2>{{ $heading }}</h2>
            <p class="narrative">{{ $text }}</p>
        @endif
    @endforeach

    <div class="footer">
        Greats Language Center — [address placeholder] — [phone placeholder] — [email placeholder]<br>
        This result was reviewed and approved by the GLC academic team. For enquiries or enrollment, please contact the center.
    </div>
</body>
</html>
