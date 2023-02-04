@extends('adminlte::page')

@section('title', 'Procesos')

@section('content_header')
    <h1>Procesos</h1>
@stop

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <div style="display: flex; justify-content: space-between; align-items: center;">

                            <span id="card_title">
                                {{ __('Proceso') }}
                            </span>

                             <div class="float-right">
                                <a href="{{ route('procesos.create') }}" class="btn btn-primary btn-sm float-right"  data-placement="left">
                                  {{ __('Nuevo Proceso') }}
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
                                        <th>Numero</th>
                                        {{-- <th>Preliminar</th> --}}
										<th>Tipo Proceso</th>
										<th>Clase Proceso</th>
										<th>Naturaleza</th>
										<th>Juzgado</th>
										{{-- <th>Cliente</th> --}}
										{{-- <th>Ciudad</th> --}}
										
										{{-- <th>Nombre</th> --}}
										<th>Presentacion</th>
										{{-- <th>Radicacion</th> --}}
										{{-- <th>Descripcion</th> --}}
										<th>Demandante</th>
										{{-- <th>Contacto Demandante</th> --}}
										<th>Demandado</th>
										{{-- <th>Contacto Demandado</th> --}}

                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($procesos as $proceso)
                                        <tr>
                                            <td>{{ ++$i }}</td>
                                            <td>{{ $proceso->nproceso }}</td>
                                            {{-- <td>{{ $proceso->preliminare->gestion }}</td> --}}
											<td>{{ $proceso->tipoproceso->nombre }}</td>
											<td>{{ $proceso->claseproceso->nombre }}</td>
											<td>{{ $proceso->naturaleza->nombre }}</td>
											<td>{{ $proceso->juzgado->nombre }}</td>
											{{-- <td>{{ $proceso->cliente->nombre }}</td> --}}
											{{-- <td>{{ $proceso->ciudade->nombre }}</td> --}}
											
											{{-- <td>{{ $proceso->nombre }}</td> --}}
											<td>{{ $proceso->fecha_presentacion }}</td>
											{{-- <td>{{ $proceso->fecha_radicacion }}</td> --}}
											{{-- <td>{{ $proceso->descripcion }}</td> --}}
											<td>{{ $proceso->demandante }}</td>
											{{-- <td>{{ $proceso->contacto_demandante }}</td> --}}
											<td>{{ $proceso->demandado }}</td>
											{{-- <td>{{ $proceso->contacto_demandado }}</td> --}}

                                            <td>
                                                <form action="{{ route('procesos.destroy',$proceso->id) }}" method="POST">
                                                    <a class="btn btn-sm btn-primary " href="{{ route('procesos.show',$proceso->id) }}"><i class="fa fa-fw fa-eye"></i></a>
                                                    <a class="btn btn-sm btn-success" href="{{ route('procesos.edit',$proceso->id) }}"><i class="fa fa-fw fa-edit"></i></a>
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
                {!! $procesos->links() !!}
            </div>
        </div>
    </div>
@endsection

@section('css')
@stop

@section('js')   
@stop