<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<products>
    @foreach($products as $product)
    <product>
        @foreach($product->values as $key => $field)
        <{{ $key }}>{{ $field['value'] }}</{{ $key }}>
        @endforeach
    </product>
    @endforeach
</products>
