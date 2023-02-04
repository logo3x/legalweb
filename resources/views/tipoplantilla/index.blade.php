@extends('adminlte::page')

@section('title', 'Tipos Plantillas')


@section('content_header')
    <h1>Tipos Plantillas</h1>
@stop

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <div style="display: flex; justify-content: space-between; align-items: center;">

                            <span id="card_title">
                                {{ __('Tipoplantilla') }}
                            </span>

                             <div class="float-right">
                                <a href="{{ route('tipoplantillas.create') }}" class="btn btn-primary btn-sm float-right"  data-placement="left">
                                  {{ __('Nuevo Tipo') }}
                                </a>
                              </div>
                        </div>
                    </div>
                    @if ($message = Session::get('success'))
                        <div class="alert alert-success">
                            <p>{{ $message }}</p>
                        </div>
                    @endif

                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="thead">
                                    <tr>
                                        <th>No</th>
                                        
										<th>Nombre</th>
										<th>Descripcion</th>

                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($tipoplantillas as $tipoplantilla)
                                        <tr>
                                            <td>{{ ++$i }}</td>
                                            
											<td>{{ $tipoplantilla->nombre }}</td>
											<td>{{ $tipoplantilla->descripcion }}</td>

                                            <td>
                                                <form action="{{ route('tipoplantillas.destroy',$tipoplantilla->id) }}" method="POST">
                                                    {{-- <a class="btn btn-sm btn-primary " href="{{ route('tipoplantillas.show',$tipoplantilla->id) }}"><i class="fa fa-fw fa-eye"></i> Ver</a> --}}
                                                    <a class="btn btn-sm btn-success" href="{{ route('tipoplantillas.edit',$tipoplantilla->id) }}"><i class="fa fa-fw fa-edit"></i></a>
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm"><i class="fa fa-fw fa-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                {!! $tipoplantillas->links() !!}
            </div>
        </div>
    </div>
@endsection

@section('css')
@stop

@section('js')   
@stop