<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function giveAttendance()
    {

        return view('admin.pages.attendance.attendance');
    }
    public function attendanceList()
    {
        $attendances = Attendance::paginate(10);
        return view('admin.pages.attendance.viewAttendance', compact('attendances'));
    }

    public function checkIn(Request $request)
    {
        // Validate  the request
        $request->validate([
            'attendance_date' => 'date|before_or_equal:today',
            'check_in_time' => 'required|date_format:H:i',
            'check_out_time' => 'nullable|date_format:H:i|after:check_in_time',
        ]);

        // Get the date and time from the form
        $selectedDate = $request->input('attendance_date') ?? now()->toDateString();
        $checkInTime = Carbon::createFromFormat('H:i', $request->input('check_in_time'));
        $checkOutTime = $request->input('check_out_time') ? Carbon::createFromFormat('H:i', $request->input('check_out_time')) : null;
        $currentMonth = Carbon::now()->monthName;

        // Check if the attendance for the selected date already exists
        $existingAttendance = Attendance::where('employee_id', auth()->user()->id)
            ->whereDate('select_date', $selectedDate)
            ->first();

        if ($existingAttendance) {
            notify()->error('Attendance already given for the selected date.');
            return redirect()->back();
        }

        // Check if the user is late (only for current date)
        $late = null;
        $checkInThreshold = Carbon::createFromTime(9, 0, 0); // Define the threshold time

        // Check if the check-in is late for the selected date
        if ($checkInTime->greaterThan($checkInThreshold)) {
            $late = $checkInTime->diff($checkInThreshold)->format('%H:%I:%S');
        }



        // Create the attendance record for the selected date
        Attendance::create([
            'employee_id' => auth()->user()->id,
            'name' => auth()->user()->name,
            'department_name' => optional(auth()->user()->employee->department)->department_name ?? 'Not specified',
            'designation_name' => optional(auth()->user()->employee->designation)->designation_name ?? 'Not specified',
            'check_in' => $checkInTime->format('H:i:s'),
            'check_out' => $checkOutTime ? $checkOutTime->format('H:i:s') : null,
            'select_date' => $selectedDate,
            'month' => $currentMonth,
            'late' => $late,
        ]);

        notify()->success('Attendance given successfully for ' . $selectedDate);
        return redirect()->back();
    }


    // updated code ends




    public function checkOut(Request $request)
    {
        // Validate the request to ensure check_out_time is provided
        $request->validate([
            'check_out_time' => 'required|date_format:H:i',
        ]);

        $existingAttendance = Attendance::where('employee_id', auth()->user()->id)
            ->whereDate('select_date', now()->toDateString())
            ->first();

        if ($existingAttendance) {
            // Check if already checked out
            if ($existingAttendance->check_out !== null) {
                notify()->error('You have already checked out for today.');
                return redirect()->back();
            }

            $checkInTime = Carbon::createFromTimeString($existingAttendance->check_in);
            $checkOutTime = Carbon::createFromFormat('H:i', $request->input('check_out_time'));
            $regularWorkingHours = $checkInTime->copy()->setTime(17, 0, 0);

            // Calculate overtime
            $overtime = $checkOutTime->diff($regularWorkingHours)->format('%H:%I:%S');

            // Update attendance record
            $existingAttendance->update([
                'check_out' => $checkOutTime->format('H:i:s'),
                'overtime' => $checkOutTime->greaterThan($regularWorkingHours) ? $overtime : null,
                'duration_minutes' => $checkOutTime->diffInMinutes($checkInTime),
            ]);

            notify()->success('You have checked out successfully.');
            if ($checkOutTime->greaterThan($regularWorkingHours)) {
                notify()->info("Overtime: $overtime");
            }
        } else {
            notify()->error('No check-in found for today.');
        }

        return redirect()->back();
    }




    // updated check out code ends


    // Delete Attendance
    public function attendanceDelete($id)
    {
        $attendance =  Attendance::find($id);
        if ($attendance) {
            $attendance->delete();
        }
        notify()->success('Deleted Successfully.');
        return redirect()->back();
    }


    public function myAttendance()
    {
        $userId = auth()->user()->id;

        // Retrieve leave records for the authenticated user only
        $attendances = Attendance::where('employee_id', $userId)
            ->paginate(10);
        return view('admin.pages.attendance.myAttendance', compact('attendances'));
    }

    // report of all attendance record
    public function attendanceReport()
    {
        $attendances = Attendance::paginate(10);
        return view('admin.pages.attendance.attendanceReport', compact('attendances'));
    }

    // report  of my attendance
    public function myAttendanceReport()
    {
        $userId = auth()->user()->id;

        // Retrieve leave records for the authenticated user only
        $attendances = Attendance::where('employee_id', $userId)
            ->paginate(10);
        return view('admin.pages.attendance.myAttendanceReport', compact('attendances'));
    }


    // search for all attendance list
    public function searchAttendanceReport(Request $request)
    {
        $searchTerm = $request->search;

        $query = Attendance::query();

        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('department_name', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('designation_name', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('select_date', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('month', 'LIKE', '%' . $searchTerm . '%');
            });
        }
        $attendances = $query->paginate(10);
        return view('admin.pages.attendance.viewSearchAttendance', compact('attendances'));
    }

    // search  my attendance
    public function searchMyAttendance(Request $request)
    {
        $userId = auth()->user()->id;
        $searchTerm = $request->search;

        $query = Attendance::where('employee_id', $userId);

        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('department_name', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('designation_name', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('select_date', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('month', 'LIKE', '%' . $searchTerm . '%');
                // Add more conditions based on your search requirements
            });
        }

        $attendances = $query->paginate(10);

        return view('admin.pages.attendance.searchMyAttendance', compact('attendances'));
    }
}
