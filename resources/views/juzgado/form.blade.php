<div class="box box-info padding-1">
    <div class="box-body">


        <div class="container">
            <div class="row">
              <div class="col">
                <div class="form-group">
                    {{ Form::label('Ciudad') }}
                    @if ($juzgado->id_ciudad==null)
                    <select   class="form-select" name="id_ciudad" id="ciudades"  style="width:100%" data-placement="left">                       
                        @foreach ($ciudades as $ciudad)                      
                            <option value="{{ $ciudad->id }}" >{{  $ciudad->nombre }}</option>     
                        @endforeach      
                    </select>
                    {{-- {{ Form::text('id_proceso', $juzgado->id_proceso, ['class' => 'form-control' . ($errors->has('id_proceso') ? ' is-invalid' : ''), 'placeholder' => 'Id Proceso']) }} --}}
                    @else
                    <select   class="form-select" name="id_ciudad" id="ciudades"  style="width:100%" data-placement="left"> 
                        <option value="{{ $juzgado->ciudade->id}}" selected>{{$juzgado->ciudade->nombre}}</option >                   
                        @foreach ($ciudades as $ciudad)                      
                            <option value="{{ $ciudad->id }}" >{{  $ciudad->nombre }}</option>     
                        @endforeach      
                    </select>
                    
                    {{-- {{ Form::text('id_proceso', $actuacione->proceso->nombre, ['class' => 'form-control' . ($errors->has('id_proceso') ? ' is-invalid' : ''), 'placeholder' => 'Id Proceso']) }} --}}
                    @endif
                    {{-- {{ Form::text('id_ciudad', $juzgado->id_ciudad, ['class' => 'form-control' . ($errors->has('id_ciudad') ? ' is-invalid' : ''), 'placeholder' => 'Id Ciudad']) }} --}}
                    {!! $errors->first('id_ciudad', '<div class="invalid-feedback">:message</div>') !!}
                </div>
              </div>
              <div class="col">
                <div class="form-group">
                    {{ Form::label('nombre') }}
                    {{ Form::text('nombre', $juzgado->nombre, ['class' => 'form-control' . ($errors->has('nombre') ? ' is-invalid' : ''), 'placeholder' => 'Nombre']) }}
                    {!! $errors->first('nombre', '<div class="invalid-feedback">:message</div>') !!}
                </div>
              </div>              
            </div>
            <div class="row">
                <div class="col">
                    <div class="form-group">
                        {{ Form::label('email1') }}
                        {{ Form::text('email1', $juzgado->email1, ['class' => 'form-control' . ($errors->has('email1') ? ' is-invalid' : ''), 'placeholder' => 'Email1']) }}
                        {!! $errors->first('email1', '<div class="invalid-feedback">:message</div>') !!}
                    </div>
                </div>
                <div class="col">
                    <div class="form-group">
                        {{ Form::label('email2') }}
                        {{ Form::text('email2', $juzgado->email2, ['class' => 'form-control' . ($errors->has('email2') ? ' is-invalid' : ''), 'placeholder' => 'Email2']) }}
                        {!! $errors->first('email2', '<div class="invalid-feedback">:message</div>') !!}
                    </div>
                </div>              
              </div>
              <div class="row">
                <div class="col">
                    <div class="form-group">
                        {{ Form::label('tel1') }}
                        {{ Form::text('tel1', $juzgado->tel1, ['class' => 'form-control' . ($errors->has('tel1') ? ' is-invalid' : ''), 'placeholder' => 'Tel1']) }}
                        {!! $errors->first('tel1', '<div class="invalid-feedback">:message</div>') !!}
                    </div>
                </div>
                <div class="col">
                    <div class="form-group">
                        {{ Form::label('tel2') }}
                        {{ Form::text('tel2', $juzgado->tel2, ['class' => 'form-control' . ($errors->has('tel2') ? ' is-invalid' : ''), 'placeholder' => 'Tel2']) }}
                        {!! $errors->first('tel2', '<div class="invalid-feedback">:message</div>') !!}
                    </div>
                </div>              
              </div>
              <div class="row">
                <div class="col">
                    <div class="form-group">
                        {{ Form::label('juez') }}
                        {{ Form::text('juez', $juzgado->juez, ['class' => 'form-control' . ($errors->has('juez') ? ' is-invalid' : ''), 'placeholder' => 'Juez']) }}
                        {!! $errors->first('juez', '<div class="invalid-feedback">:message</div>') !!}
                    </div>
                </div>
                <div class="col">
                    <div class="form-group">
                        {{ Form::label('secretario') }}
                        {{ Form::text('secretario', $juzgado->secretario, ['class' => 'form-control' . ($errors->has('secretario') ? ' is-invalid' : ''), 'placeholder' => 'Secretario']) }}
                        {!! $errors->first('secretario', '<div class="invalid-feedback">:message</div>') !!}
                    </div>
                </div>              
              </div>
          </div>






        
       
        
        
        
       
        
        
        

    </div>
    <div class="box-footer mt20">
        <button type="submit" class="btn btn-primary">Enviar</button>
    </div>
</div>