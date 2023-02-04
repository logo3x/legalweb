@extends('adminlte::page')

@section('title', 'Preliminares')

@section('content_header')
    <h1>Preliminares</h1>
@stop

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                                 <h4>Lista de preliminares</h4>
                                <lottie-player src="https://assets7.lottiefiles.com/packages/lf20_gKFtRN3PXQ.json"  background="transparent"  speed="1"  style="width: 200px; height: 100px;"  loop  autoplay></lottie-player>
                                    
                                <div class="float-right">
                                    <a href="{{ route('preliminares.create') }}" class="btn btn-primary btn-sm float-right"  data-placement="left">
                                    {{ __('Nuevo Relato') }}
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
                                        
										<th>Fecha</th>
										<th>Cliente</th>
										{{-- <th>Relato</th> --}}
										<th>Gestion</th>
										<th>Des Gestion</th>

                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($preliminares as $preliminare)
                                        <tr>
                                            <td>{{ ++$i }}</td>
                                            
											<td>{{ $preliminare->fecha }}</td>
											<td>{{ $preliminare->cliente->nombre }}</td>
											{{-- <td>{{ $preliminare->relato }}</td> --}}
											<td>{{ $preliminare->gestion }}</td>
											<td>{{ $preliminare->des_gestion }}</td>

                                            <td>
                                                <form action="{{ route('preliminares.destroy',$preliminare->id) }}" method="POST">
                                                    <a class="btn btn-sm btn-primary " href="{{ route('preliminares.show',$preliminare->id) }}"><i class="fa fa-fw fa-eye"></i></a>
                                                    <a class="btn btn-sm btn-success" href="{{ route('preliminares.edit',$preliminare->id) }}"><i class="fa fa-fw fa-edit"></i></a>
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
                {!! $preliminares->links() !!}
            </div>
        </div>
    </div>
@endsection
@section('css')
@stop

@section('js')  
<script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script> 
@stop