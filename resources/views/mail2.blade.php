{{-- <h1>Welcome</h1>
<p>Your Google Sheet updated successfully.</p>

@foreach ($summary as $doctor => $data)
    <p><strong>{{ $doctor }}</strong></p>
    <ul>
        <li>Total Entries: {{ $data['total'] ?? 0 }}</li>
        <li>Total YES: {{ $data['totalYES'] ?? 0 }}</li>
        <li>Total NO: {{ $data['totalNO'] ?? 0 }}</li>
    </ul>
@endforeach

<a href="https://docs.google.com/spreadsheets/d/1XoN2n1YZLGEwgbEuwr2pvR4RI21ziEg9sQ-fKbLb1ds/edit?gid=350623793">
    Click here to view your data
</a>
 --}}

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Table</title>
</head>

<body style="font-family: Arial, sans-serif; background-color: #f8f9fa; margin: 0; padding: 0;">

    @php
        $grandTotal = 0;
        $grandTotalYES = 0;
        $grandTotalNO = 0;
    @endphp

    @foreach ($summary as $doctor => $data)
        @php
            $total = $data['total'] ?? 0;
            $totalYES = $data['totalYES'] ?? 0;
            $totalNO = $data['totalNO'] ?? 0;

            $grandTotal += $total;
            $grandTotalYES += $totalYES;
            $grandTotalNO += $totalNO;
        @endphp
    @endforeach
    <ul>
        <li>Total Entries: {{ $grandTotal }}</li>
        <li>Total YES: {{ $grandTotalYES }}</li>
        <li>Total NO: {{ $grandTotalNO }}</li>
    </ul>

    <table align="center" width="100%" cellpadding="0" cellspacing="0" border="0"
        style="max-width: 800px; background-color: white; border-collapse: collapse; margin: 0 auto;">


        <tr>
            <td style="padding: 10px;">
                <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse: collapse;">
                    <tr>
                        <!-- Left Column -->


                        <!-- Right Column -->
                        <td width="100%" style="vertical-align: top; padding: 5px;">
                            <h6
                                style="text-align: center; background-color: #95d7e0; color: white; padding: 10px; margin: 0; font-size: 14px;">
                                Reports</h6>
                            <table width="100%" cellpadding="5" cellspacing="0" border="1"
                                style="border-collapse: collapse; font-size: 14px; text-align: center;">
                                <thead>
                                    <tr style="background-color: #e0e0e0;">
                                        <th>Name Of Doctor </th>
                                        <th>Total</th>
                                        <th>Yes</th>
                                        <th>No</th>
                                    </tr>
                                </thead>
                                <tbody>

                                    @foreach ($summary as $doctor => $data)
                                        <tr>
                                            <td>{{ $doctor }}</td>
                                            <td>{{ $data['total'] ?? 0 }}</td>
                                            <td>{{ $data['totalYES'] ?? 0 }}</td>
                                            <td>{{ $data['totalNO'] ?? 0 }}</td>
                                        </tr>
                                    @endforeach
                                    @if (empty($summary))
                                        <tr>
                                            <td colspan="4" style="text-align: center;">No submitted reports</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr>
            <td style="text-align: center; padding: 10px;">
                <a href="https://docs.google.com/spreadsheets/d/{{ env('GOOGLE_SHEET_ID') }}/edit?gid=350623793"
                    style="color: #007bff; text-decoration: none;">Click here to view your data</a>
            </td>
        </tr>

    </table>

</body>

</html>
