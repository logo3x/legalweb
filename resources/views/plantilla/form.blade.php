<div class="box box-info padding-1">
    <div class="box-body">
        

        <div class="container text-center">
            <div class="row">
                <div class="col-4">
                    <div class="form-group">
                        {{ Form::label('Tipo de Plantilla') }}
                        <select   class="form-select" name="id_tipoplantillas"  style="width:100%" data-placement="left" id="tipoplantilla">                       
                            @foreach ($tipoplantillas as $tipos)                      
                                <option value="{{ $tipos->id }}" >{{  $tipos->nombre }}</option>     
                            @endforeach      
                        </select>
                        {!! $errors->first('id_tipoplantillas', '<div class="invalid-feedback">:message</div>') !!}
                       
                        {{-- {{ Form::text('id_tipoplantillas', $plantilla->id_tipoplantillas, ['class' => 'form-control' . ($errors->has('id_tipoplantillas') ? ' is-invalid' : ''), 'placeholder' => 'Id Tipoplantillas']) }} --}}
                        
                    </div>
                </div>
                <div class="col-8">
                    <div class="form-group">
                        {{ Form::label('Nombre') }}
                        {{ Form::text('nombre', $plantilla->nombre, ['class' => 'form-control' . ($errors->has('nombre') ? ' is-invalid' : ''), 'placeholder' => 'Nombre']) }}
                        {!! $errors->first('nombre', '<div class="invalid-feedback">:message</div>') !!}
                    </div>
                </div>
            </div>
              <div class="row">
                <div class="col-12">
                    <div class="form-group">
                        {{ Form::label('Descripcion') }}
                        {{ Form::text('descripcion', $plantilla->descripcion, ['class' => 'form-control' . ($errors->has('descripcion') ? ' is-invalid' : ''), 'placeholder' => 'Descripcion']) }}
                        {!! $errors->first('descripcion', '<div class="invalid-feedback">:message</div>') !!}
                    </div>
                </div>
            </div>
            
                    <div class="form-group">         
                        @if ($plantilla->anexo==false)
                        <div class="row">
                            <div class="col-12">
                                    <div class="mb-3">
                                        <label for="formFile" class="form-label">Anexo</label>
                                        <input name="anexo" class="form-control" type="file" id="formFile">
                                    </div>
                            </div>
                        </div>
                          @else 
                          <div class="row">
                                <div class="col-2">             
                                    {{ Form::label('Plantilla') }}
                                    <a href="{{$plantilla->getFirstMediaUrl('anexo_plantillas')}}" target="_black"><br>Descargar<lottie-player src="https://assets8.lottiefiles.com/packages/lf20_szdrhwiq.json"  background="transparent"  speed="1"  style="width: 100px; height: 100px;"  loop  autoplay></lottie-player></a>
                                </div>
                                <div class="col-10">
                                        <div class="mb-3">
                                            <label for="formFile" class="form-label">Actualizar Anexo</label>
                                            <input name="anexo" class="form-control" type="file" id="formFile">
                                        </div>
                                </div>
                             </div>
                            
                        @endif
                        {{-- {{ Form::file('anexo', $plantilla->anexo, ['class' => 'form-control form-control-lg' . ($errors->has('anexo') ? ' is-invalid' : ''), 'placeholder' => 'Anexo']) }} --}}
                        {!! $errors->first('anexo', '<div class="invalid-feedback">:message</div>') !!}
                    </div>
                </div>
            </div>
        
    </div>
        
       
        
        
       

    
    <div class="box-footer mt20">
        <button type="submit" class="btn btn-primary">Enviar</button>
    </div>
</div>