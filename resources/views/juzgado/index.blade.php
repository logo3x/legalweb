@extends('adminlte::page')

@section('title', 'Juzgados')

@section('content_header')
    <h1>Juzgados</h1>
@stop

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <div style="display: flex; justify-content: space-between; align-items: center;">

                            <span id="card_title">
                                {{ __('Juzgado') }}
                            </span>

                             <div class="float-right">
                                <a href="{{ route('juzgados.create') }}" class="btn btn-primary btn-sm float-right"  data-placement="left">
                                  {{ __('Nuevo Juzgado') }}
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
                                        
										<th>Ciudad</th>
										<th>Nombre</th>
										{{-- <th>Email1</th> --}}
										{{-- <th>Email2</th> --}}
										<th>Tel1</th>
										{{-- <th>Tel2</th> --}}
										<th>Juez</th>
										{{-- <th>Secretario</th> --}}

                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($juzgados as $juzgado)
                                        <tr>
                                            <td>{{ ++$i }}</td>
                                            
											<td>{{ $juzgado->ciudade->nombre }}</td>
											<td>{{ $juzgado->nombre }}</td>
											{{-- <td>{{ $juzgado->email1 }}</td> --}}
											{{-- <td>{{ $juzgado->email2 }}</td> --}}
											<td>{{ $juzgado->tel1 }}</td>
											{{-- <td>{{ $juzgado->tel2 }}</td> --}}
											<td>{{ $juzgado->juez }}</td>
											{{-- <td>{{ $juzgado->secretario }}</td> --}}

                                            <td>
                                                <form action="{{ route('juzgados.destroy',$juzgado->id) }}" method="POST">
                                                    <a class="btn btn-sm btn-primary " href="{{ route('juzgados.show',$juzgado->id) }}"><i class="fa fa-fw fa-eye"></i></a>
                                                    <a class="btn btn-sm btn-success" href="{{ route('juzgados.edit',$juzgado->id) }}"><i class="fa fa-fw fa-edit"></i></a>
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
                {!! $juzgados->links() !!}
            </div>
        </div>
    </div>
@endsection

@section('css')
@stop

@section('js')   
@stop