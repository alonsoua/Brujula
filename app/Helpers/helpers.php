<?php

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


function formatNameImage($image64, $alias) {
  // imagen esta en base64 ?
  if ( substr( $image64, 0, strpos( $image64, ',' ) + 1 ) != 'h') {
    $extension = explode('/', explode(':', substr($image64, 0, strpos($image64, ';')))[1])[1];
    $imagen = $alias.'-'.Str::random(10).'.'.$extension;
  } else {
    $imagen = null;
  }
  return $imagen;
}

// ? $ruta en storage/app/public/
// ? $image64 = Imagen codificada en base64
// ? $nameImage = Nombre de la imagen a guardar
function saveStorageImagen( $ruta, $image64, $nameImage ) {
  $replace = substr( $image64, 0, strpos( $image64, ',' ) + 1 );
  $image   = str_replace( $replace, '', $image64 );
  $image   = str_replace( ' ', '+', $image );
  Storage::disk( $ruta )->put( $nameImage, base64_decode( $image ) );
}

function deleteStorageImagen( $ruta, $nameImage ) {
  Storage::disk($ruta)->delete( $nameImage );
}
