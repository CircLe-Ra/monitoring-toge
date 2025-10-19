<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Penanaman</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 6px; text-align: left; vertical-align: top; }
        th { background: #eee; }
        img { width: 100px; height: auto; border: 1px solid #ccc; }
        ul { list-style-type: none; padding: 0; margin: 0; }
        .text-center { text-align: center; }
        em { color: #777; }
    </style>
</head>
<body>
<h3>Laporan Penanaman Bulan {{ $month }}/{{ $year }}</h3>

<table>
    <thead>
    <tr>
        <th>Nama Tanaman</th>
        <th>Tanggal Tanam</th>
        <th>Estimasi Panen</th>
        <th>Tahapan</th>
        <th>Perendaman</th>
        <th>Perkecambahan</th>
        <th>Pertumbuhan Daun</th>
        <th>Siap Panen</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($plantings as $plant)
        <tr>
            <td>{{ $plant->plant_name }}</td>
            <td>{{ $plant->planted_at->format('d M Y') }}</td>
            <td>{{ $plant->estimated_days_to_harvest }} hari</td>
            <td>
                <ul>
                    @foreach ($plant->growthStages as $stage)
                        <li>{{ $stage->stage_name }} ({{ $stage->day_start }}–{{ $stage->day_end }} hari)</li>
                    @endforeach
                </ul>
            </td>

            @php
                $stages = [
                    'Perendaman' => null,
                    'Perkecambahan' => null,
                    'Pertumbuhan Daun' => null,
                    'Siap Panen' => null
                ];

                foreach ($plant->growthStages as $stage) {
                    $stages[$stage->stage_name] = $stage;
                }
            @endphp

            @foreach ($stages as $name => $stage)
                <td class="text-center">
                    @if ($stage && $stage->photo)
                        <img src="{{ public_path('storage/' . $stage->photo) }}" alt="{{ $name }}">
                    @else
                        <em>Tidak ada foto</em>
                    @endif
                </td>
            @endforeach
        </tr>
    @endforeach
    </tbody>
</table>

</body>
</html>
