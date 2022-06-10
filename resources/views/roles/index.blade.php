@extends('adminlte::page')

@section('title', 'Roles')

@section('content_header')
    <h1>Roles</h1>
@stop

@section('content')
<section class="section">
   
    <div class="section-body">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
    
                    @can('crear-rol')
                    <a class="btn btn-warning" href="{{ route('roles.create') }}">Nuevo</a>                        
                    @endcan
    
            
                        <table class="table table-striped mt-2">
                            <thead>                                                       
                                <th>Rol</th>
                                <th>Acciones</th>
                            </thead>  
                            <tbody>
                            @foreach ($roles as $role)
                            <tr>                           
                                <td>{{ $role->name }}</td>
                                <td>                                
                                    @can('editar-rol')
                                        <a class="btn btn-primary" href="{{ route('roles.edit',$role->id) }}">Editar</a>
                                    @endcan
                                    
                                    @can('borrar-rol')
                                        {!! Form::open(['method' => 'DELETE','route' => ['roles.destroy', $role->id],'style'=>'display:inline']) !!}
                                            {!! Form::submit('Borrar', ['class' => 'btn btn-danger']) !!}
                                        {!! Form::close() !!}
                                    @endcan
                                </td>
                            </tr>
                            @endforeach
                            </tbody>               
                        </table>

                        <!-- Centramos la paginacion a la derecha -->
                        <div class="pagination justify-content-end">
                            {!! $roles->links() !!} 
                        </div>                    
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@stop

@section('css')
    
@stop

@section('js')
    
@stop

