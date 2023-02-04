<div class="box box-info padding-1">
    <div class="box-body">
        


        <div class="container">
            <div class="row">
              <div class="col">
                <div class="form-group">
                    {{ Form::label('Ciudad') }}
                            <select   class="form-select" name="id_ciudad" id="ciudades"  style="width:100%" data-placement="left">                       
                                @foreach ($ciudades as $ciudad)                      
                                    <option value="{{ $ciudad->id }}" >{{  $ciudad->nombre }}</option>     
                                @endforeach      
                            </select>
                            {{-- {{ Form::text('id_ciudad', $juzgado->id_ciudad, ['class' => 'form-control' . ($errors->has('id_ciudad') ? ' is-invalid' : ''), 'placeholder' => 'Id Ciudad']) }} --}}
                            {!! $errors->first('id_ciudad', '<div class="invalid-feedback">:message</div>') !!}
                    {{-- {{ Form::text('id_ciudad', $cliente->id_ciudad, ['class' => 'form-control' . ($errors->has('id_ciudad') ? ' is-invalid' : ''), 'placeholder' => 'Id Ciudad']) }} --}}            
                </div>
              </div>
              <div class="col">
                <div class="form-group">
                    {{ Form::label('Nombre') }}
                    {{ Form::text('nombre', $cliente->nombre, ['class' => 'form-control' . ($errors->has('nombre') ? ' is-invalid' : ''), 'placeholder' => 'Nombre']) }}
                    {!! $errors->first('nombre', '<div class="invalid-feedback">:message</div>') !!}
                </div>
              </div>              
            </div>
            <div class="row">
                <div class="col">
                    <div class="form-group">
                        {{ Form::label('Descripcion') }}
                        {{ Form::text('descripcion', $cliente->descripcion, ['class' => 'form-control' . ($errors->has('descripcion') ? ' is-invalid' : ''), 'placeholder' => 'Descripcion']) }}
                        {!! $errors->first('descripcion', '<div class="invalid-feedback">:message</div>') !!}
                    </div>
                </div>                           
              </div>
              <div class="row">
                <div class="col">
                    <div class="form-group">
                        {{ Form::label('Direccion') }}
                        {{ Form::text('direccion', $cliente->direccion, ['class' => 'form-control' . ($errors->has('direccion') ? ' is-invalid' : ''), 'placeholder' => 'Direccion']) }}
                        {!! $errors->first('direccion', '<div class="invalid-feedback">:message</div>') !!}
                    </div>
                </div>
                <div class="col">
                    <div class="form-group">
                        {{ Form::label('Email') }}
                        {{ Form::text('email', $cliente->email, ['class' => 'form-control' . ($errors->has('email') ? ' is-invalid' : ''), 'placeholder' => 'Email']) }}
                        {!! $errors->first('email', '<div class="invalid-feedback">:message</div>') !!}
                    </div>
                </div>              
              </div>
              <div class="row">
                <div class="col">
                    <div class="form-group">
                        {{ Form::label('Celular1') }}
                        {{ Form::text('celular1', $cliente->celular1, ['class' => 'form-control' . ($errors->has('celular1') ? ' is-invalid' : ''), 'placeholder' => 'Celular1']) }}
                        {!! $errors->first('celular1', '<div class="invalid-feedback">:message</div>') !!}
                    </div>
                </div>
                <div class="col">
                    <div class="form-group">
                        {{ Form::label('Celular2') }}
                        {{ Form::text('celular2', $cliente->celular2, ['class' => 'form-control' . ($errors->has('celular2') ? ' is-invalid' : ''), 'placeholder' => 'Celular2']) }}
                        {!! $errors->first('celular2', '<div class="invalid-feedback">:message</div>') !!}
                    </div>
                </div>              
              </div>
          </div>






        
        
        
        
        
        
       

    </div>
    <div class="box-footer mt20">
        <button type="submit" class="btn btn-primary">Enviar</button>
    </div>
</div>