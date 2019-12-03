<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en"><head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
</head><body>
@php
    $fecha_inscripcion = $cursoInscripcions->inscripcion->modified;
    if($fecha_inscripcion!=null)
    {
        $fecha_inscripcion =  Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $fecha_inscripcion );
    }

    // Alias para un render mas legible
    $inscripcion = $cursoInscripcions->inscripcion;
    $curso= $cursoInscripcions->curso;

    $ciclo = $inscripcion->ciclo;
    $centro = $inscripcion->centro;
    $alumno = $inscripcion->alumno;
    $persona = $alumno->persona;

@endphp

    <div style="font-size: 14px">
            <table style="width: 100%;" class="header">
                <tr>
                    <td>
                        <img src="escudo.png" style="margin-left:30px;width: 100px">
                        <div style="font-style: italic">Provincia de Tierra del Fuego,</div>
                        <div style="font-style: italic">Antártida e Islas del Atlántico Sur</div>
                        <div style="font-style: italic;color: #5e5e5e;">República Argentina</div>
                        <div style="font-style: italic">Ministerio de Educación</div>
                        @if(isset($centro->nivel_servicio) && $centro->sector == 'ESTATAL')
                            @switch($centro->nivel_servicio)
                                @case('Común - Inicial')
                                @case('Común - Primario')
                                @case('Común - Especial')
                                    <div style="font-weight: bold;">Supervisión Técnica-Supervisión Escolar</div>
                                    @break
                                @case('Adultos - Primario')
                                @case('Adultos - Secundario')
                                    <div style="font-weight: bold;">Dirección de Modalidades Educativas</div>
                                    @break
                                @case('Común - Superior')
                                    <div style="font-weight: bold;">Dirección de Superior</div>
                                    @break
                                @default
                            @endswitch
                        @else
                            <div style="font-weight: bold;">Dirección de Privadas</div>
                        @endif
                    </td>
                    <td>
                        <div style="text-align:right; line-height: 150px; font-size: 10px">“2019 – AÑO DEL CENTENARIO DEL NACIMIENTO DE EVA DUARTE DE PERÓN”</div>
                    </td>
                </tr>
            </table>
            <hr>
            <div style="text-align:center">
                <h3>CONSTANCIA DE ALUMNO REGULAR</h3>

                <div>
                    {{ $centro->nombre }}
                    C.U.E. N°
                    {{ $centro->cue }}
                </div>

                <div style="padding:10px; font-size:12px;font-weight: bold;">
                    {{ strtoupper($centro->direccion) }} -
                    {{ strtoupper($centro->ciudad->nombre) }}
                </div>
            </div>
            <p>
                Se hace constar que <b>{{ strtoupper($persona->apellidos) }}, {{ strtoupper($persona->nombres) }}</b>,
                documento tipo: <b>{{ strtoupper($persona->documento_tipo) }}</b>, N°
                <b>{{ strtoupper($persona->documento_nro) }}</b>
                es alumno regular de este establecimiento y se encuentra cursando año <b>{{ $curso->anio }}</b>

                @if($centro->nivel_servicio=='Común - Inicial' && $curso->tipo=='Múltiple' )
                    @switch($curso->anio)
                    @case('Sala de 3 años')
                    Múltiple (3 y 4 años)
                    @break
                    @case('Sala de 4 años')
                    Múltiple (4 y 5 años)
                    @break
                    @endswitch
                @endif

                , división <b>{{ $curso->division }}</b>
                del servicio y nivel <b>{{ $centro->nivel_servicio }}</b>

                @if ($inscripcion->tipo_inscripcion == 'Estudiante de Intercambio')
                    (inscripción tipo: <b>{{ $inscripcion->tipo_inscripcion }}</b>)
                @endif
            </p>

            @if(!empty($inscripcion->alumno->observaciones))
            <h4>Datos complementarios</h4>

            <p>
                {{ $inscripcion->alumno->observaciones }}
            </p>
            @endif
            <p>
		A pedido del/a interesado/a y al solo efecto de ser presentada ante quien corresponda 
                se extiende la presente, sin enmiendas ni raspaduras en la ciudad de <b>{{ $centro->ciudad->nombre }}</b>, Provincia de Tierra del Fuego,
                el <b>{{ \Carbon\Carbon::now()->format('d/m/Y') }}</b>.
            </p>

            <h3 style="text-align: center;padding-top:40px;padding-bottom:100px;">DOCUMENTO NO VÁLIDO PARA EL COBRO DE SALARIO FAMILIAR
            </h3>

            <div style="float:right;border-top: 1px solid #000;">Sello y firma de la autoridad institucional</div>

            <span style="clear:both;color:#3a3a3a;font-size:11px;font-style: italic;font-weight: bold;">Las Islas Malvinas, Georgias, Sandwich del Sur, son y serán Argentinas</span>
            <hr />
        </div>
</body></html>
