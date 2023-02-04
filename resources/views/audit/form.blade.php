<div class="box box-info padding-1">
    <div class="box-body">
        
        <div class="form-group">
            {{ Form::label('user_type') }}
            {{ Form::text('user_type', $audit->user_type, ['class' => 'form-control' . ($errors->has('user_type') ? ' is-invalid' : ''), 'placeholder' => 'User Type']) }}
            {!! $errors->first('user_type', '<div class="invalid-feedback">:message</div>') !!}
        </div>
        <div class="form-group">
            {{ Form::label('user_id') }}
            {{ Form::text('user_id', $audit->user_id, ['class' => 'form-control' . ($errors->has('user_id') ? ' is-invalid' : ''), 'placeholder' => 'User Id']) }}
            {!! $errors->first('user_id', '<div class="invalid-feedback">:message</div>') !!}
        </div>
        <div class="form-group">
            {{ Form::label('event') }}
            {{ Form::text('event', $audit->event, ['class' => 'form-control' . ($errors->has('event') ? ' is-invalid' : ''), 'placeholder' => 'Event']) }}
            {!! $errors->first('event', '<div class="invalid-feedback">:message</div>') !!}
        </div>
        <div class="form-group">
            {{ Form::label('auditable_type') }}
            {{ Form::text('auditable_type', $audit->auditable_type, ['class' => 'form-control' . ($errors->has('auditable_type') ? ' is-invalid' : ''), 'placeholder' => 'Auditable Type']) }}
            {!! $errors->first('auditable_type', '<div class="invalid-feedback">:message</div>') !!}
        </div>
        <div class="form-group">
            {{ Form::label('auditable_id') }}
            {{ Form::text('auditable_id', $audit->auditable_id, ['class' => 'form-control' . ($errors->has('auditable_id') ? ' is-invalid' : ''), 'placeholder' => 'Auditable Id']) }}
            {!! $errors->first('auditable_id', '<div class="invalid-feedback">:message</div>') !!}
        </div>
        <div class="form-group">
            {{ Form::label('old_values') }}
            {{ Form::text('old_values', $audit->old_values, ['class' => 'form-control' . ($errors->has('old_values') ? ' is-invalid' : ''), 'placeholder' => 'Old Values']) }}
            {!! $errors->first('old_values', '<div class="invalid-feedback">:message</div>') !!}
        </div>
        <div class="form-group">
            {{ Form::label('new_values') }}
            {{ Form::text('new_values', $audit->new_values, ['class' => 'form-control' . ($errors->has('new_values') ? ' is-invalid' : ''), 'placeholder' => 'New Values']) }}
            {!! $errors->first('new_values', '<div class="invalid-feedback">:message</div>') !!}
        </div>
        <div class="form-group">
            {{ Form::label('url') }}
            {{ Form::text('url', $audit->url, ['class' => 'form-control' . ($errors->has('url') ? ' is-invalid' : ''), 'placeholder' => 'Url']) }}
            {!! $errors->first('url', '<div class="invalid-feedback">:message</div>') !!}
        </div>
        <div class="form-group">
            {{ Form::label('ip_address') }}
            {{ Form::text('ip_address', $audit->ip_address, ['class' => 'form-control' . ($errors->has('ip_address') ? ' is-invalid' : ''), 'placeholder' => 'Ip Address']) }}
            {!! $errors->first('ip_address', '<div class="invalid-feedback">:message</div>') !!}
        </div>
        <div class="form-group">
            {{ Form::label('user_agent') }}
            {{ Form::text('user_agent', $audit->user_agent, ['class' => 'form-control' . ($errors->has('user_agent') ? ' is-invalid' : ''), 'placeholder' => 'User Agent']) }}
            {!! $errors->first('user_agent', '<div class="invalid-feedback">:message</div>') !!}
        </div>
        <div class="form-group">
            {{ Form::label('tags') }}
            {{ Form::text('tags', $audit->tags, ['class' => 'form-control' . ($errors->has('tags') ? ' is-invalid' : ''), 'placeholder' => 'Tags']) }}
            {!! $errors->first('tags', '<div class="invalid-feedback">:message</div>') !!}
        </div>

    </div>
    <div class="box-footer mt20">
        <button type="submit" class="btn btn-primary">Submit</button>
    </div>
</div>