@extends('adminlte::page')

@section('title', 'Anexos')

@section('content_header')
    <h1>Anexos</h1>
@stop

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                                 <h4>Lista de Anexos</h4>
                                <lottie-player src="https://assets3.lottiefiles.com/packages/lf20_ZRp9hN/attachment_05.json"  background="transparent"  speed="1"  style="width: 150px; height: 150px;"  loop  autoplay></lottie-player>
                                    
                                <div class="float-right">
                                    <a href="{{ route('anexos.create') }}" class="btn btn-primary btn-sm float-right"  data-placement="left">
                                    {{ __('Nuevo Anexo') }}
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
										<th>Proceso</th>
										<th>Nombre</th>
										<th>Descripcion</th>
										<th>Anexo</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($anexos as $anexo)
                                        <tr>
                                            <td>{{ ++$i }}</td>
                                            
											<td>{{ $anexo->proceso->nproceso }}</td>
											<td>{{ $anexo->nombre }}</td>
											<td>{{ $anexo->descripcion }}</td>
											<td>                                            
                                                @if ($anexo->anexo==false)
                                                     <lottie-player src="https://assets1.lottiefiles.com/packages/lf20_9lxy9vc3.json"  background="transparent"  speed="1"  style="width: 70px; height: 70px;"  loop  autoplay></lottie-player>
                                                 
                                                @else    
                                                    <a href="{{$anexo->getFirstMediaUrl('anexo_anexo')}}" target="_black"><br>Ver/Descargar<lottie-player src="https://assets8.lottiefiles.com/packages/lf20_szdrhwiq.json"  background="transparent"  speed="1"  style="width: 70px; height: 70px;"  loop  autoplay></lottie-player></a>
                                                     
                                                @endif 
                                            </td>

                                            <td>
                                                <form action="{{ route('anexos.destroy',$anexo->id) }}" method="POST">
                                                    {{-- <a class="btn btn-sm btn-primary " href="{{ route('anexos.show',$anexo->id) }}"><i class="fa fa-fw fa-eye"></i> Ver</a> --}}
                                                    <a class="btn btn-sm btn-success" href="{{ route('anexos.edit',$anexo->id) }}"><i class="fa fa-fw fa-edit"></i></a>
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
                {!! $anexos->links() !!}
            </div>
        </div>
    </div>
@endsection

@section('css')
@stop

@section('js')   
<script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>  
@stop