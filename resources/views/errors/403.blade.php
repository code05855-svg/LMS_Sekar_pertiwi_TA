@extends('errors.minimal')
@section('kode', '403')
@section('judul', 'Akses ditolak')
@section('pesan', filled($exception->getMessage()) && ! in_array($exception->getMessage(), ['', 'Forbidden', 'Not Found', 'Page Expired', 'Server Error', 'Service Unavailable', 'Too Many Requests', 'This action is unauthorized.']) ? $exception->getMessage() : 'Anda tidak punya izin untuk membuka halaman ini.')
