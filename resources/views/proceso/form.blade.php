<div class="box box-info padding-1">
    <div class="box-body">
        



        <div class="container text-center">
            <div class="row">
              <div class="col">
                <div class="form-group">
                    {{ Form::label('Cliente') }}

                    @if ($proceso->id_cliente==null)
                    <select   class="form-select" name="id_cliente" id="clientes"  style="width:100%" data-placement="left" required>  
                        <option>   Seleccione un Cliente </option>                  
                        @foreach ($clientes as $cliente)                      
                            <option value="{{ $cliente->id }}" >{{  $cliente->nombre }}</option>     
                        @endforeach      
                    </select>
                    {{-- {{ Form::text('id_proceso', $proceso->id_proceso, ['class' => 'form-control' . ($errors->has('id_proceso') ? ' is-invalid' : ''), 'placeholder' => 'Id Proceso']) }} --}}
                    @else
                    <select   class="form-select" name="id_cliente" id="clientes"  style="width:100%" data-placement="left" required> 
                        <option value="{{ $proceso->cliente->id}}" selected>{{$proceso->cliente->nombre}}</option >                   
                        @foreach ($clientes as $cliente)                      
                            <option value="{{ $cliente->id }}" >{{  $cliente->nombre }}</option>     
                        @endforeach      
                    </select>
                    @endif


                   {{--  {{ Form::text('id_cliente', $proceso->id_cliente, ['class' => 'form-control' . ($errors->has('id_cliente') ? ' is-invalid' : ''), 'placeholder' => 'Id Cliente']) }} --}}
                    {!! $errors->first('id_cliente', '<div class="invalid-feedback">:message</div>') !!}
                </div>
              </div>
              <div class="col">
                <div class="form-group">
                    {{ Form::label('Preliminar') }}
                    <select   class="form-select" name="id_preliminar" id="preliminares"  style="width:100%" data-placement="left" required> 
                        
                    </select>                      
                    {{-- </select>
                    @if ($proceso->id_preliminar==null)
                    <select   class="form-select" name="id_preliminar" id="preliminares"  style="width:100%" data-placement="left">                       
                        @foreach ($preliminares as $preliminar)                      
                            <option value="{{ $preliminar->id }}" >{{  $preliminar->gestion }}</option>     
                        @endforeach      
                    </select> --}}
                    {{-- {{ Form::text('id_proceso', $proceso->id_proceso, ['class' => 'form-control' . ($errors->has('id_proceso') ? ' is-invalid' : ''), 'placeholder' => 'Id Proceso']) }} --}}
                    
                   {{--  @else
                    <select   class="form-select" name="id_preliminar" id="_preliminares"  style="width:100%" data-placement="left"> 
                        <option value="{{ $proceso->preliminare->id}}" selected>{{$proceso->preliminare->des_gestion}}</option >                   
                        @foreach ($preliminares as $preliminar)                      
                            <option value="{{ $preliminar->id }}" >{{  $preliminar->nombre }}</option>     
                        @endforeach      
                    </select>
                    @endif --}}
                    {{-- {{ Form::text('id_preliminar', $proceso->id_preliminar, ['class' => 'form-control' . ($errors->has('id_preliminar') ? ' is-invalid' : ''), 'placeholder' => 'Id Tipoprocesos']) }} --}}
                    {!! $errors->first('id_preliminar', '<div class="invalid-feedback">:message</div>') !!}
                </div>
              </div>              
            </div>
            <div class="row">
                <div class="col">
                    <div class="form-group">
                        {{ Form::label('Nombre') }}
                        {{ Form::text('nombre', $proceso->nombre, ['class' => 'form-control' . ($errors->has('nombre') ? ' is-invalid' : ''), 'placeholder' => 'Nombre']) }}
                        {!! $errors->first('nombre', '<div class="invalid-feedback">:message</div>') !!}
                    </div>
                </div>
                <div class="col">
                    <div class="form-group">
                        {{ Form::label('Numero de Proceso') }}
                        {{ Form::text('nproceso', $proceso->nproceso, ['class' => 'form-control' . ($errors->has('nproceso') ? ' is-invalid' : ''), 'placeholder' => 'Nproceso']) }}
                        {!! $errors->first('nproceso', '<div class="invalid-feedback">:message</div>') !!}
                    </div>
                </div>              
            </div>
            <div class="row">
                <div class="col">
                    <div class="form-group">
                        {{ Form::label('Tipo de Procesos') }}                         
                    @if ($proceso->id_tipoproceso==null)
                    <select   class="form-select" name="id_tipoprocesos" id="tipoproceso"  style="width:100%" data-placement="left">                       
                        @foreach ($tipoprocesos as $tipos)                      
                            <option value="{{ $tipos->id }}" >{{  $tipos->nombre }}</option>     
                        @endforeach      
                    </select>
                    {{-- {{ Form::text('id_proceso', $tramite->id_proceso, ['class' => 'form-control' . ($errors->has('id_proceso') ? ' is-invalid' : ''), 'placeholder' => 'Id Proceso']) }} --}}
                    @else
                    <select   class="form-select" name="id_tipoprocesos" id="tipoproceso"  style="width:100%" data-placement="left"> 
                        <option value="{{ $proceso->tipoproceso->id}}" selected>{{$proceso->tipoproceso->nombre}}</option >                   
                        @foreach ($tipoprocesos as $tipos)                      
                            <option value="{{ $tipos->id }}" >{{  $tipos->nombre }}</option>     
                        @endforeach      
                    </select>
                    @endif                 
                        {{-- {{ Form::text('id_tipoprocesos', $proceso->id_tipoprocesos, ['class' => 'form-control' . ($errors->has('id_tipoprocesos') ? ' is-invalid' : ''), 'placeholder' => 'Id Tipoprocesos']) }} --}}
                        {!! $errors->first('id_tipoprocesos', '<div class="invalid-feedback">:message</div>') !!}
                    </div>
                </div>
                <div class="col">
                    <div class="form-group">
                        {{ Form::label('Clase') }}

                        @if ($proceso->id_claseproceso==null)
                        <select   class="form-select" name="id_claseproceso" id="claseproceso"  style="width:100%" data-placement="left">                       
                            @foreach ($claseprocesos as $tipos)                      
                                <option value="{{ $tipos->id }}" >{{  $tipos->nombre }}</option>     
                            @endforeach      
                        </select>
                        {{-- {{ Form::text('id_proceso', $tramite->id_proceso, ['class' => 'form-control' . ($errors->has('id_proceso') ? ' is-invalid' : ''), 'placeholder' => 'Id Proceso']) }} --}}
                        @else
                        <select   class="form-select" name="id_claseproceso" id="claseproceso"  style="width:100%" data-placement="left"> 
                            <option value="{{ $proceso->claseproceso->id}}" selected>{{$proceso->claseproceso->nombre}}</option >                   
                            @foreach ($claseprocesos as $tipos)                      
                                <option value="{{ $tipos->id }}" >{{  $tipos->nombre }}</option>     
                            @endforeach      
                        </select>
                        @endif 


                        {{-- {{ Form::text('id_claseproceso', $proceso->id_claseproceso, ['class' => 'form-control' . ($errors->has('id_claseproceso') ? ' is-invalid' : ''), 'placeholder' => 'Id Claseproceso']) }} --}}
                        {!! $errors->first('id_claseproceso', '<div class="invalid-feedback">:message</div>') !!}
                    </div>
                </div> 
                <div class="col">
                    <div class="form-group">
                        {{ Form::label('Naturaleza') }}

                        @if ($proceso->id_naturaleza==null)
                        <select   class="form-select" name="id_naturaleza" id="naturaleza"  style="width:100%" data-placement="left">                       
                            @foreach ($naturalezas as $tipos)                      
                                <option value="{{ $tipos->id }}" >{{  $tipos->nombre }}</option>     
                            @endforeach      
                        </select>
                        {{-- {{ Form::text('id_proceso', $tramite->id_proceso, ['class' => 'form-control' . ($errors->has('id_proceso') ? ' is-invalid' : ''), 'placeholder' => 'Id Proceso']) }} --}}
                        @else
                        <select   class="form-select" name="id_naturaleza" id="naturaleza"  style="width:100%" data-placement="left"> 
                            <option value="{{ $proceso->naturaleza->id}}" selected>{{$proceso->naturaleza->nombre}}</option >                   
                            @foreach ($naturalezas as $tipos)                      
                                <option value="{{ $tipos->id }}" >{{  $tipos->nombre }}</option>     
                            @endforeach      
                        </select>
                        @endif 

                        
                       {{--  {{ Form::text('id_naturaleza', $proceso->id_naturaleza, ['class' => 'form-control' . ($errors->has('id_naturaleza') ? ' is-invalid' : ''), 'placeholder' => 'Id Naturaleza']) }} --}}
                        {!! $errors->first('id_naturaleza', '<div class="invalid-feedback">:message</div>') !!}
                    </div>
                </div>              
            </div>
            <div class="row">
                <div class="col">
                    <div class="form-group">
                        {{ Form::label('Juzgado') }}

                        @if ($proceso->id_naturaleza==null)
                        <select   class="form-select" name="id_juzgado" id="juzgado"  style="width:100%" data-placement="left">                       
                            @foreach ($juzgados as $tipos)                      
                                <option value="{{ $tipos->id }}" >{{  $tipos->nombre }}</option>     
                            @endforeach      
                        </select>
                        {{-- {{ Form::text('id_proceso', $tramite->id_proceso, ['class' => 'form-control' . ($errors->has('id_proceso') ? ' is-invalid' : ''), 'placeholder' => 'Id Proceso']) }} --}}
                        @else
                        <select   class="form-select" name="id_juzgado" id="juzgado"  style="width:100%" data-placement="left"> 
                            <option value="{{ $proceso->juzgado->id}}" selected>{{$proceso->juzgado->nombre}}</option >                   
                            @foreach ($juzgados as $tipos)                      
                                <option value="{{ $tipos->id }}" >{{  $tipos->nombre }}</option>     
                            @endforeach      
                        </select>
                        @endif 

                        {{-- {{ Form::text('id_juzgado', $proceso->id_juzgado, ['class' => 'form-control' . ($errors->has('id_juzgado') ? ' is-invalid' : ''), 'placeholder' => 'Id Juzgado']) }} --}}
                        {!! $errors->first('id_juzgado', '<div class="invalid-feedback">:message</div>') !!}
                    </div>
                </div>
                <div class="col">
                    <div class="form-group">
                        {{ Form::label('Ciudad') }}

                        @if ($proceso->id_ciudad==null)
                        <select   class="form-select" name="id_ciudad" id="ciudades"  style="width:100%" data-placement="left">                       
                            @foreach ($ciudades as $tipos)                      
                                <option value="{{ $tipos->id }}" >{{  $tipos->nombre }}</option>     
                            @endforeach      
                        </select>
                        {{-- {{ Form::text('id_proceso', $tramite->id_proceso, ['class' => 'form-control' . ($errors->has('id_proceso') ? ' is-invalid' : ''), 'placeholder' => 'Id Proceso']) }} --}}
                        @else
                        <select   class="form-select" name="id_ciudad" id="ciudades"  style="width:100%" data-placement="left"> 
                            <option value="{{ $proceso->ciudade->id}}" selected>{{$proceso->ciudade->nombre}}</option >                   
                            @foreach ($ciudades as $tipos)                      
                                <option value="{{ $tipos->id }}" >{{  $tipos->nombre }}</option>     
                            @endforeach      
                        </select>
                        @endif 

                       {{--  {{ Form::text('id_ciudad', $proceso->id_ciudad, ['class' => 'form-control' . ($errors->has('id_ciudad') ? ' is-invalid' : ''), 'placeholder' => 'Id Ciudad']) }} --}}
                        {!! $errors->first('id_ciudad', '<div class="invalid-feedback">:message</div>') !!}
                    </div>
                </div>              
            </div>
            <div class="row">
                <div class="col">
                    <div class="form-group">
                        {{ Form::label('Fecha de Presentacion') }}
                        {{ Form::date('fecha_presentacion', $proceso->fecha_presentacion, ['class' => 'form-control' . ($errors->has('fecha_presentacion') ? ' is-invalid' : ''), 'placeholder' => 'Fecha Presentacion']) }}
                        {!! $errors->first('fecha_presentacion', '<div class="invalid-feedback">:message</div>') !!}
                    </div>
                </div> 
                <div class="col">
                    <div class="form-group">
                        {{ Form::label('Fecha Radicacion') }}
                        {{ Form::date('fecha_radicacion', $proceso->fecha_radicacion, ['class' => 'form-control' . ($errors->has('fecha_radicacion') ? ' is-invalid' : ''), 'placeholder' => 'Fecha Radicacion']) }}
                        {!! $errors->first('fecha_radicacion', '<div class="invalid-feedback">:message</div>') !!}
                    </div>
                </div>            
            </div>
            <div class="row">                
                <div class="col">
                    <div class="form-group">
                        {{ Form::label('Descripcion') }}
                        {{ Form::textarea('descripcion', $proceso->descripcion, ['class' => 'form-control' . ($errors->has('descripcion') ? ' is-invalid' : ''), 'placeholder' => 'Descripcion' ,'id' => 'myeditorinstance']) }}
                        {!! $errors->first('descripcion', '<div class="invalid-feedback">:message</div>') !!}
                    </div>
                </div>              
            </div>
            <div class="row">
                <div class="col">
                    <div class="form-group">
                        {{ Form::label('Demandante') }}
                        {{ Form::text('demandante', $proceso->demandante, ['class' => 'form-control' . ($errors->has('demandante') ? ' is-invalid' : ''), 'placeholder' => 'Demandante']) }}
                        {!! $errors->first('demandante', '<div class="invalid-feedback">:message</div>') !!}
                    </div>
                </div>
                <div class="col">
                    <div class="form-group">
                        {{ Form::label('Contacto rapido Demandante') }}
                        {{ Form::text('contacto_demandante', $proceso->contacto_demandante, ['class' => 'form-control' . ($errors->has('contacto_demandante') ? ' is-invalid' : ''), 'placeholder' => 'Contacto Demandante']) }}
                        {!! $errors->first('contacto_demandante', '<div class="invalid-feedback">:message</div>') !!}
                    </div>
                </div>              
            </div>
            <div class="row">
                <div class="col">
                    <div class="form-group">
                        {{ Form::label('Demandado') }}
                        {{ Form::text('demandado', $proceso->demandado, ['class' => 'form-control' . ($errors->has('demandado') ? ' is-invalid' : ''), 'placeholder' => 'Demandado']) }}
                        {!! $errors->first('demandado', '<div class="invalid-feedback">:message</div>') !!}
                    </div>
                </div>
                <div class="col">
                    <div class="form-group">
                        {{ Form::label('contacto_demandado') }}
                        {{ Form::text('contacto_demandado', $proceso->contacto_demandado, ['class' => 'form-control' . ($errors->has('contacto_demandado') ? ' is-invalid' : ''), 'placeholder' => 'Contacto Demandado']) }}
                        {!! $errors->first('contacto_demandado', '<div class="invalid-feedback">:message</div>') !!}
                    </div>
                </div>                
            </div>
            
          </div>



        
        
        
        
        
        
      
       
        
        
        
        
        
        
        
       
        

    </div>
    <div class="box-footer mt20">
        <button type="submit" class="btn btn-primary">Enviar</button>
    </div>
</div>