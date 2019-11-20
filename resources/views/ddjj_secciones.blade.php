<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en"><head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        @page {
            margin: 130px 25px 80px 25px;
        }
        html, body {
            display: block;
        }
        header { 
            left:0;
            right:0;
            position: fixed; 
            top:-120px;
            padding-bottom:0;
            margin-bottom:0;
        }
        footer { 
            width: 100%; 
            position: fixed;
            margin-top:15px;
            bottom: 10px; 
            text-align: right; 
            border-top: 1px solid black;
        }
        main {
            margin-bottom:50px;
            font-family: sans-serif; 
        }
        tr:nth-child(even) {background-color: #f2f2f2;}
}
        
    </style>
</head><body><script type="text/php">
    if ( isset($pdf) ) {
        $font = $fontMetrics->getFont("sans-serif");
        $pdf->page_text(780, 570, "Página {PAGE_NUM} - {PAGE_COUNT}", $font, 8, array(0,0,0));
    }
</script><header>
        <table style="width:100%;">
            <tr>
                <td width="100px">
                    <img src="escudo.png" style="width: 80px">
                </td>
                <td width="200px" style="font-size: 13px;">
                    <div style="font-style: italic">Provincia de Tierra del Fuego,</div>
                    <div style="font-style: italic">Antártida e Islas del Atlántico Sur</div>
                    <div style="font-style: italic;color: #5e5e5e;">República Argentina</div>
                    <div style="font-style: italic">Ministerio de Educación</div>
                </td>
                <td>
                    <div style="text-align:right; font-size: 11px">“2019 – AÑO DEL CENTENARIO DEL NACIMIENTO DE EVA DUARTE DE PERÓN”</div>
                </td>
            </tr>
        </table>
        @if(count($matriculas) > 0)
        <div style="margin:15px 20px 0 10px; font-size:15px;font-family:sans-serif;">
            <strong>{{$matriculas[0]["cue"]}} | {{$matriculas[0]["nombre"]}}</strong>
        </div>
        @endif
    </header><footer style="text-align:center">
        <div style="margin-top:3px;float:left; font-size:11px;font-family:sans-serif;">Fecha de Impresión: {{ \Carbon\Carbon::now()->format('d-m-Y H:m') }}<br><br>
            <span style="font-style: italic;">Documento obtenido en <strong>SIEP TDF</strong></span>
        </div>
        <div style="float:right;border-top: 1px solid #000; font-size:12px;">Firma y Aclaración de la Autoridad Institucional</div>
        <span style="clear:both;color:#3a3a3a;font-size:11px;font-style: italic;font-weight: bold;">Las Islas Malvinas, Georgias, Sandwich del Sur, son y serán Argentinas</span>
        <span style="float:right;clear:both;color:#3a3a3a;font-size:11px;font-family:sans-serif;"></span>
    </footer><main>
        <table width="100%" class="table-hover">
            <thead>
                <tr style="font-size:13px; text-align:center;">
                    <th>Año</th>
                    <th>División</th>
                    <th>Turno</th>
                    <th>Hs Cátedras</th>
                    <th>Res. Pedagógica</th>
                    <th>Instr. legal de Creación</th>
                    <th>Titulación</th>
                    <th>Plazas</th>
                    <th>Matrículas</th>
                    <th>Vacantes</th>
                    @if(isset($report_type))
                        <th>Promociones</th>
                        @if($report_type === "repitencias")
                            <th>Repitencias</th>
                        @endif
                    @else
                        <th>Observaciones</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @if(isset($matriculas) && count($matriculas) > 0)
                    @foreach($matriculas as $mat)
                    <tr style="font-size:11px; text-align:center;">
                        <td>{{$mat["anio"]}}</td>
                        <td>{{$mat["division"]}}</td>
                        <td>{{$mat["turno"]}}</td>
                        <td>{{$mat["hs_catedras"]}}</td>
                        <td>{{$mat["titulacion"]["reso_pedagogica"]}}</td>
                        <td>{{$mat["reso_presupuestaria"]}}</td>
                        <td>{{$mat["titulacion"]["nombre_abreviado"]}}</td>
                        <td>{{$mat["plazas"]}}</td>
                        <td>{{$mat["matriculas"]}}</td>
                        <td>{{$mat["vacantes"]}}</td>
                        @if(isset($report_type))
                            <td>{{$mat["promociones"]}}</td>
                            @if($report_type == "repitencias")
                                <td>{{$mat["repitencias"]}}</td>
                            @endif
                        @else
                            <td>{{$mat["observaciones"]}}</td>
                        @endif
                    </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </main></body></html>


