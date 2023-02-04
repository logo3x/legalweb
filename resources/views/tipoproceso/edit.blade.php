@extends('adminlte::page')

@section('title', 'Tipo de Proceso')


@section('content_header')
    <h1>Tipo de Proceso</h1>
@stop


@section('content')
    <section class="content container-fluid">
        <div class="">
            <div class="col-md-12">

                @includeif('partials.errors')

                <div class="card card-default">
                    <div class="card-header">
                        <span class="card-title">Actualizar Tipo de Proceso</span>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('tipoprocesos.update', $tipoproceso->id) }}"  role="form" enctype="multipart/form-data">
                            {{ method_field('PATCH') }}
                            @csrf

                            @include('tipoproceso.form')

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('css')
@stop

@section('js')   
@stop
