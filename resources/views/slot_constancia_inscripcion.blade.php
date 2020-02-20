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
                <h2 style="text-align: right">INSCRIPCIÓN NÚMERO | {{ $inscripcion->legajo_nro }}</h2>
                <div style="text-align: right; font-size: 12px">“2020 - Año del General Manuel Belgrano”</div>
            </td>
        </tr>
    </table>
    <br>
    @if ($inscripcion->tipo_inscripcion == 'Estudiante de Intercambio')
        <h3>CONSTANCIA DE INSCRIPCIÓN | Estado: {{ $inscripcion->estado_inscripcion }} | {{ $inscripcion->tipo_inscripcion }}</h3>
    @else
        <h3>CONSTANCIA DE INSCRIPCIÓN | Estado: {{ $inscripcion->estado_inscripcion }}</h3>
    @endif
    <p>
        @if(isset($centro->nivel_servicio) && $centro->nivel_servicio=='Común - Inicial' && $centro->nivel_servicio=='Común - Primario')
            La Supervisión Técnica de Supervisión Escolar,
        @else
            Se
        @endif
        deja constancia que el/la niño/a <b>{{ strtoupper($persona->apellidos) }}, {{ strtoupper($persona->nombres) }}</b>,
        documento tipo: <b>{{ strtoupper($persona->documento_tipo) }}</b>, N°
        <b>{{ strtoupper($persona->documento_nro) }}</b>,
        ha sido INSCRIPTO/A en esta dependencia, para la Escuela Provincial/Jardín de Infantes: <b>{{ $centro->nombre }}</b>
        en el grado/sala <b>{{ $curso->anio }} {{ $curso->division }} {{ $curso->turno }}</b>

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

        para el Ciclo Escolar <b>{{ $ciclo->nombre }}</b>
        en <b>{{ $centro->ciudad->nombre }}</b> el día: <b>{{ ($fecha_inscripcion!=null) ? $fecha_inscripcion->format('d/m/Y') :'__/__/____' }}</b>
    </p>
    <div style="height: 80px;"></div>
    <div style="float:right;border-top: 1px solid #000;">Sello y firma de autoridad institucional</div>
    <span style="clear:both;color:#3a3a3a;font-size:11px;font-style: italic;font-weight: bold;">Las Islas Malvinas, Georgias, Sandwich del Sur, son y serán Argentinas</span>
    <hr />
</div>