@extends('adminlte::page')

@section('title', 'Tramites')


@section('content_header')
    <h1>Clase Proceso</h1>
@stop

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <div style="display: flex; justify-content: space-between; align-items: center;">

                            <span id="card_title">
                                {{-- {{ __('Claseproceso') }} --}}
                            </span>

                             <div class="float-right">
                                <a href="{{ route('claseprocesos.create') }}" class="btn btn-primary btn-sm float-right"  data-placement="left">
                                  {{ __('Nueva Clase') }}
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
                                    @foreach ($claseprocesos as $claseproceso)
                                        <tr>
                                            <td>{{ ++$i }}</td>
                                            
											<td>{{ $claseproceso->nombre }}</td>
											<td>{{ $claseproceso->descripcion }}</td>

                                            <td>
                                                @can('administrador')
                                                <form action="{{ route('claseprocesos.destroy',$claseproceso->id) }}" method="POST">
                                                    {{-- <a class="btn btn-sm btn-primary " href="{{ route('claseprocesos.show',$claseproceso->id) }}"><i class="fa fa-fw fa-eye"></i> Ver</a> --}}
                                                    <a class="btn btn-sm btn-success" href="{{ route('claseprocesos.edit',$claseproceso->id) }}"><i class="fa fa-fw fa-edit"></i></a>
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm"><i class="fa fa-fw fa-trash"></i></button>
                                                </form>
                                                @endcan
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                {!! $claseprocesos->links() !!}
            </div>
        </div>
    </div>
@endsection

@section('css')
@stop

@section('js')   
@stop