@extends('layouts.app')
@php($header = '')
@php($footer = '')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6">{{ __('Advanced UI Components Demo') }}</h1>

    <section class="mb-12">
        <h2 class="text-xl font-semibold mb-3">{{ __('Photo Gallery') }}</h2>
        <x-gallery :images="$galleryImages" :autoplay="true" :interval-ms="3000" />
    </section>

    <section class="mb-12 prose dark:prose-invert">
        <h2 class="text-xl font-semibold mb-3">{{ __('Pull Quote') }}</h2>
        <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Maiores, voluptates.</p>
        <x-pull-quote align="right" text="Design is not just what it looks like and feels like. Design is how it works." attribution="Steve Jobs" />
        <p>Dolor sit amet consectetur adipisicing elit. Eos pariatur explicabo tenetur earum provident distinctio impedit.</p>
    </section>

    <section class="mb-12">
        <h2 class="text-xl font-semibold mb-3">{{ __('Social Embed (fallback)') }}</h2>
        <div class="grid md:grid-cols-3 gap-4">
            <x-social-embed provider="twitter" url="https://twitter.com/jack/status/20" />
            <x-social-embed provider="facebook" url="https://www.facebook.com/zuck/posts/10102577175875681" />
            <x-social-embed provider="instagram" url="https://www.instagram.com/p/Cx12345678/" />
        </div>
    </section>

    <section class="mb-12">
        <h2 class="text-xl font-semibold mb-3">{{ __('Charts') }}</h2>
        <x-chart type="line" :csv="$chartCsv" :height="320" />
        <div class="mt-6 grid md:grid-cols-2 gap-6">
            <x-chart type="bar" :csv="$chartCsv" :height="240" />
            <x-chart type="pie" :csv="$chartCsv" :height="240" />
        </div>
    </section>
</div>
@endsection


