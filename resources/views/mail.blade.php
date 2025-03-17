<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Table</title>
</head>

<body style="font-family: Arial, sans-serif; background-color: #f8f9fa; margin: 0; padding: 0;">

    <table align="center" width="100%" cellpadding="0" cellspacing="0" border="0"
        style="max-width: 800px; background-color: white; border-collapse: collapse; margin: 0 auto;">
        <tr>
            <td style="padding: 20px; text-align: center;">
                <h5
                    style="background-color: #e3f2fd; padding: 10px; margin: 0; border: 1px solid #b9dce1; font-size: 16px;">
                    Report Table</h5>
            </td>
        </tr>

        <tr>
            <td style="padding: 10px;">
                <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse: collapse;">
                    <tr>
                        <!-- Left Column -->
                        <td width="50%" style="vertical-align: top; padding: 5px;">


                            <h6
                                style="text-align: center; background-color: #8d0803; color: white; padding: 10px; margin: 0; font-size: 14px;">
                                Not Submitted Reports</h6>
                            <table width="100%" cellpadding="5" cellspacing="0" border="1"
                                style="border-collapse: collapse; font-size: 12px; text-align: center;">
                                <thead>
                                    <tr style="background-color: #e0e0e0;">
                                        <th>Date</th>
                                        <th>Doctor</th>
                                        <th>Vendor</th>
                                        <th>Case#</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $prevDoctor = null; @endphp
                                    @foreach ($no as $entry)
                                        @if ($prevDoctor !== null && $prevDoctor !== $entry['doctor'])
                                            <tr style="background-color: #cfe2ff;">
                                                <td colspan="4"></td>
                                            </tr>
                                        @endif
                                        <tr>
                                            <td>{{ date('F d, Y', strtotime($entry['date'])) }}</td>
                                            <td>{{ $entry['doctor'] }}</td>
                                            <td>{{ $entry['vendor'] }}</td>
                                            <td>{{ $entry['case'] }}</td>
                                        </tr>
                                        @php $prevDoctor = $entry['doctor']; @endphp
                                    @endforeach
                                    @if (empty($no))
                                        <tr>
                                            <td colspan="4" style="text-align: center;">No pending reports</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </td>

                        <!-- Right Column -->
                        @if (!empty($yes))
                            <td width="50%" style="vertical-align: top; padding: 5px;">
                                <h6
                                    style="text-align: center; background-color: #01391c; color: white; padding: 10px; margin: 0; font-size: 14px;">
                                    Submitted Reports</h6>
                                <table width="100%" cellpadding="5" cellspacing="0" border="1"
                                    style="border-collapse: collapse; font-size: 12px; text-align: center;">
                                    <thead>
                                        <tr style="background-color: #e0e0e0;">
                                            <th>Date</th>
                                            <th>Doctor</th>
                                            <th>Vendor</th>
                                            <th>Case#</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $prevDoctor = null; @endphp
                                        @foreach ($yes as $entry)
                                            @if ($prevDoctor !== null && $prevDoctor !== $entry['doctor'])
                                                <tr style="background-color: #cfe2ff;">
                                                    <td colspan="4"></td>
                                                </tr>
                                            @endif
                                            <tr>
                                                <td>{{ date('F d, Y', strtotime($entry['date'])) }}</td>
                                                <td>{{ $entry['doctor'] }}</td>
                                                <td>{{ $entry['vendor'] }}</td>
                                                <td>{{ $entry['case'] }}</td>
                                            </tr>
                                            @php $prevDoctor = $entry['doctor']; @endphp
                                        @endforeach
                                        @if (empty($yes))
                                            <tr>
                                                <td colspan="4" style="text-align: center;">No submitted reports</td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </td>
                        @endif
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
