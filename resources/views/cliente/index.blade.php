@extends('adminlte::page')

@section('title', 'Clientes')

@section('content_header')
    <h1>Clientes</h1>
@stop

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                     <h4>Lista de Clientes</h4>
                                    <lottie-player src="https://assets6.lottiefiles.com/packages/lf20_hmnohyvb.json"  background="transparent"  speed="1"  style="width: 200px; height: 100px;"  loop  autoplay></lottie-player>
                                        
                                    <div class="float-right">
                                        <a href="{{ route('clientes.create') }}" class="btn btn-primary btn-sm float-right"  data-placement="left">
                                        {{ __('Nuevo Cliente') }}
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
										<th>Descripcion</th>
										<th>Direccion</th>
										<th>Email</th>
										<th>Celular1</th>
										<th>Celular2</th>

                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($clientes as $cliente)
                                        <tr>
                                            <td>{{ ++$i }}</td>
                                            
											<td>{{ $cliente->ciudade->nombre }}</td>
											<td>{{ $cliente->nombre }}</td>
											<td>{{ $cliente->descripcion }}</td>
											<td>{{ $cliente->direccion }}</td>
											<td>{{ $cliente->email }}</td>
											<td>{{ $cliente->celular1 }}</td>
											<td>{{ $cliente->celular2 }}</td>

                                            <td>
                                                <form action="{{ route('clientes.destroy',$cliente->id) }}" method="POST">
                                                    {{-- <a class="btn btn-sm btn-primary " href="{{ route('clientes.show',$cliente->id) }}"><i class="fa fa-fw fa-eye"></i> Ver</a> --}}
                                                    <a class="btn btn-sm btn-success" href="{{ route('clientes.edit',$cliente->id) }}"><i class="fa fa-fw fa-edit"></i></a>
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
                {!! $clientes->links() !!}
            </div>
        </div>
    </div>
@endsection

@section('css')
@stop

@section('js')   
<script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
@stop