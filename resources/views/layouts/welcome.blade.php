@extends('layouts/frontend')

@section('content')
<!-- Hero Section -->
<div class="bg-white py-16 border-b">
    <div class="max-w-7xl mx-auto px-4 text-center">
        <h1 class="text-4xl md:text-6xl font-extrabold text-gray-900 mb-4">
            Find Your Dream Job in <span class="text-primary">Bangladesh</span>
        </h1>
        <p class="text-lg text-gray-600 mb-8 max-w-2xl mx-auto">
            Connecting talented professionals with the best companies across the country.
        </p>

        <!-- Search Bar -->
        <div class="max-w-3xl mx-auto bg-white p-2 rounded-xl shadow-lg border flex flex-col md:flex-row gap-2">
            <input type="text" placeholder="Job Title, Skills..." class="flex-1 border-none focus:ring-0 px-4 py-3 text-gray-700">
            <select class="border-none focus:ring-0 px-4 py-3 text-gray-500 border-l">
                <option>All Categories</option>
                <option>IT & Software</option>
                <option>Marketing</option>
            </select>
            <button class="bg-primary text-white px-8 py-3 rounded-lg font-bold">Search</button>
        </div>
    </div>
</div>

<!-- Featured Jobs -->
<div class="max-w-7xl mx-auto px-4 py-12">
    <h2 class="text-2xl font-bold mb-8">Latest Job Openings</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @foreach(\App\Models\Job::where('is_active', true)->latest()->take(6)->get() as $job)
        <div class="bg-white p-6 rounded-xl border hover:shadow-md transition">
            <div class="flex justify-between items-start">
                <div>
                    <h3 class="font-bold text-lg text-gray-900">{{ $job->title }}</h3>
                    <p class="text-primary font-medium">{{ $job->company->name ?? 'Company Name' }}</p>
                    <div class="flex items-center text-sm text-gray-500 mt-2 space-x-4">
                        <span>📍 {{ $job->location }}</span>
                        <span>💰 {{ $job->salary_range ?? 'Negotiable' }}</span>
                    </div>
                </div>
                <a href="#" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm font-bold">View Details</a>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection