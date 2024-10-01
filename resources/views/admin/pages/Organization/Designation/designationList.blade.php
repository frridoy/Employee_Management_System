@extends('admin.master')

@section('content')
<div class="shadow p-4 d-flex justify-content-between align-items-center ">
    <h4 class="text-uppercase">Designation List</h4>
    <div>
        <a href="{{ route('organization.designation') }}" class="btn btn-success p-2 text-lg rounded-pill"><i
                class="fa-solid fa-plus me-1"></i>Create
            Designation</a>
    </div>
</div>
<div class="container my-5 py-5">

    <div class="d-flex justify-content-end">
        <div class="input-group rounded w-25 mb-5">
            <form action="{{ route('searchDesignation') }}" method="get">
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="Search..." name="search">
                    <button type="submit" class="input-group-text border-0 bg-transparent" id="search-addon"
                        style="display: inline;">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
    {{-- Department Table start --}}
    <div>
        <table class="table align-middle mb-4 text-center bg-white">
            <thead class="bg-light">
                <tr>
                    <th>SL NO</th>
                    <th>Designation ID</th>
                    <th>Designation Name</th>
                    <th>Department Name</th>
                    <th>Salary Class</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($designations as $key => $item)
                <tr>
                    <td>{{ $key + 1 }}</td>
                    <td>{{ $item->designation_id }}</td>
                    <td>{{ $item->designation_name }}</td>
                    <td>{{ optional($item->department)->department_name }}</td>
                    {{-- <td>{{ $item->salary->salary_class }}</td> --}}
                    <td>{{ optional($item->salary)->salary_class }}</td>
                    <td>
                        <a class="btn btn-success rounded-pill fw-bold text-white"
                            href="{{ route('designation.edit', $item->id) }}">Edit</a>
                        <a class="btn btn-danger rounded-pill fw-bold text-white"
                            href="{{ route('designation.delete', $item->id) }}">Delete</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection