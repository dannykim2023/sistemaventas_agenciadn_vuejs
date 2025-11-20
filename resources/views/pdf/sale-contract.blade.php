<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <title>Venta #{{ $sale->series }}-{{ str_pad($sale->number, 6, '0', STR_PAD_LEFT) }}</title>
  <style>
    @page { size: A4; margin: 18mm 16mm 16mm 16mm; }
    * { box-sizing: border-box; }
    html, body, table, th, td, div, span {
      font-family: "DejaVu Sans", sans-serif;
      color: #1a1a1a;
    }
    body { font-size: 13.5px; line-height: 1.55; background: #fff; }

    .muted { color: #555; }
    .strong { font-weight: 700; }
    .right  { text-align: right; }
    .num    { text-align: right; white-space: nowrap; }

    /* ===== Encabezado ===== */
    .header { width: 100%; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 18px; }
    .header td { vertical-align: middle; }
    .logo { width: 250px; }
    .title {
      font-size: 28px;
      text-transform: uppercase;
      font-weight: 800;
      text-align: right;
      color: #000;
    }
    .subtitle {
      font-size: 13px;
      text-align: right;
      color: #555;
    }

    /* ===== Bloque de info ===== */
    .info { width: 100%; margin-top: 6px; margin-bottom: 14px; }
    .info td { vertical-align: top; padding: 3px 0; font-size: 12.5px; }

    /* Nota de pago / aviso */
    .note {
      border-left: 3px solid #000;
      padding: 10px 12px;
      background: #f8f8f8;
      margin: 12px 0 16px;
      font-size: 12.5px;
    }

    /* ===== Tabla de ítems ===== */
    table.items { width: 100%; border-collapse: collapse; margin-top: 8px; }
    .items thead th {
      background: #000;
      color: #fff;
      font-weight: 700;
      text-transform: uppercase;
      font-size: 12.5px;
      padding: 10px 9px;
      text-align: left;
      border-bottom: 2px solid #000;
    }
    .items tbody tr:nth-child(odd) { background: #f9f9f9; }
    .items tbody td {
      padding: 9px 9px;
      border-bottom: 1px solid #949494ff;
      font-size: 12.5px;
    }

    /* ===== Totales ===== */
    table.wrap-totals { width: 100%; margin-top: 18px; }
    table.totals { width: 57%; margin-left: auto; border-collapse: collapse; }
    .totals td {
      padding: 9px 10px;
      border-bottom: 1px solid #949494ff;
      font-size: 12.5px;
    }
    .totals td:first-child {
      font-weight: 700;
      text-align: right;
      width: 55%;
    }

    .grand-row { background: #dadadaff; border: none; }
    .grand-box {
      display: table;
      width: 100%;
      padding: 0;
    }
    .grand-label, .grand-amount {
      display: table-cell;
      vertical-align: middle;
      font-weight: 800;
      font-size: 15px;
    }
    .grand-label { text-align: left; }
    .grand-amount { text-align: right; }

    /* Nota al lado de los totales */
    .totals-note {
      font-size: 12.5px;
      color: #333;
      background: #f8f8f8;
      padding: 10px 12px;
      border-left: 3px solid #000;
      line-height: 1.4;
    }

    /* ===== Firma ===== */
    .signature-box {
      display: inline-block;
      width: 52%;
      text-align: center;
      margin-top: 16px;
    }
    .signature-label { font-size: 12px; color: #333; margin-bottom: 2px; }
    .signature-line  { width: 100%; border-top: 1px solid #000; margin: 0 auto 8px auto; }
    .signature-name  { font-weight: 700; font-size: 13.5px; }
    .signature-role  { font-size: 12.5px; color: #555; }
    .signature-img   { width: 110px; display: block; margin: 0 auto 6px auto; }

    /* ===== Títulos de secciones ===== */
    .section-title {
      margin-top: 8px;
      margin-bottom: 4px;
      font-size: 14px;
      font-weight: 700;
      text-transform: uppercase;
    }
    .section-divider {
      border-top: 1px solid #000;
      margin: 16px 0 10px;
    }

    /* ===== Footer simple ===== */
    .footer { width: 100%; font-size: 12.5px; margin-top: 10px; }
    .footer td { vertical-align: top; padding-right: 12px; }
    .pill {
      display: inline-block;
      background: #000;
      color: #fff;
      padding: 6px 12px;
      border-radius: 14px;
      font-weight: 700;
      font-size: 12.5px;
    }

    /* ===== Salto de página ===== */
    .page-break { page-break-before: always; }
  </style>
</head>
<body>
  @php
      $issue = optional($sale->issue_date)->format('d/m/Y');
      $due   = optional($sale->due_date)->format('d/m/Y');
      $paid  = $sale->paid_amount ?? 0;
      $total = $sale->total ?? 0;
      $balance = $sale->balance ?? max(0, $total - $paid);
  @endphp

  <!-- ===== HEADER ===== -->
  <table class="header">
    <tr>
      <td>
        <img class="logo" src="{{ public_path('imagenes/logoagenciadnHorizontal.png') }}" alt="Logo">
      </td>
      <td>
        <div class="title">CONTRATO / RESUMEN DE VENTA</div>
        <div class="subtitle">
          Venta #{{ $sale->series }}-{{ str_pad($sale->number, 6, '0', STR_PAD_LEFT) }}
        </div>
      </td>
    </tr>
  </table>

  <!-- ===== INFO EMPRESA / CLIENTE ===== -->
  <table class="info">
    <tr>
      <td style="width: 55%; padding-right: 12px;">
        <div class="strong">Agencia DN – Software & Marketing S.A.C.</div>
        <div class="muted">
          RUC: 20641261493<br>
          Calle Robinson 113 – Surquillo – Lima<br>
          Tel: +51 959 114 988<br>
          Correo: soporte@agenciadn.pe
        </div>
      </td>
      <td style="width: 45%;">
        <div class="strong">Cliente:</div>
        <div class="muted">
          {{ $sale->customer->name ?? '—' }}<br>
          {{ $sale->customer->email ?? $sale->customer->contact_email ?? '' }}<br>
          {{ $sale->customer->document_number ?? $sale->customer->ruc ?? $sale->customer->dni ?? '' }}
        </div>
      </td>
    </tr>
    <tr>
      <td class="muted" style="padding-top: 8px;">
        <span class="strong">N° Venta:</span>
        {{ $sale->series }}-{{ str_pad($sale->number, 6, '0', STR_PAD_LEFT) }}<br>
        <span class="strong">Fecha de emisión:</span> {{ $issue ?: '—' }}<br>
        @if($due)
          <span class="strong">Fecha de vencimiento:</span> {{ $due }}
        @endif
      </td>
      <td class="muted" style="padding-top: 8px;">
        <span class="strong">Condición de pago:</span>
        {{ $sale->payment_term ?? 'Por acuerdo entre las partes' }}<br>
        <span class="strong">Moneda:</span>
        {{ $sale->currency ?? 'PEN' }} (S/)
      </td>
    </tr>
  </table>

  <!-- ===== MÉTODOS DE PAGO ===== -->
  <div class="note">
    <span class="strong">Métodos de pago:</span>
    YAPE/PLIN: 973 777 665 · Interbank Cta. Cte.: 200-3007316583 ·
    CCI: 003-200-003007316583-36 · BCP: 41002140899017 ·
    CCI BCP: 0024101024089901799 ·
    Titular: Lorenzo Daniel Sancho Osco.
  </div>

  <!-- ===== ITEMS / SERVICIOS INCLUIDOS EN LA VENTA ===== -->
  <div class="section-title">Detalle de servicios / productos</div>

  <table class="items">
    <thead>
      <tr>
        <th style="width: 50%;">Descripción</th>
        <th style="width: 15%;">Precio</th>
        <th style="width: 15%;">Cantidad</th>
        <th style="width: 20%;">Total</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($sale->items as $item)
        @php
          $unit  = $item->unit_price ?? 0;
          $qty   = $item->quantity ?? 0;
          $line  = $item->total ?? ($unit * $qty);
        @endphp
        <tr>
          <td>
            <div class="strong">
              {{ $item->description ?? $item->name ?? 'Item' }}
            </div>
            @if(!empty($item->notes))
              <div class="muted" style="font-size:12px;">{{ $item->notes }}</div>
            @endif
          </td>
          <td class="num">S/ {{ number_format($unit, 2) }}</td>
          <td class="num">{{ number_format($qty, 0) }}</td>
          <td class="num">S/ {{ number_format($line, 2) }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="4" class="muted" style="text-align:center;">
            Sin ítems registrados.
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>

  <!-- ===== TOTALES + NOTA + FIRMA ===== -->
  <table class="wrap-totals">
    <tr>
      <!-- Nota / Condiciones de pago concretas de la venta -->
      <td style="width:54%; vertical-align:top; padding-right:0px;">
        <div class="totals-note">
          <span class="strong">Resumen de pagos:</span><br>
          Total de la venta: <strong>S/ {{ number_format($total, 2) }}</strong><br>
          Monto pagado a la fecha: <strong>S/ {{ number_format($paid, 2) }}</strong><br>
          Saldo pendiente: <strong>S/ {{ number_format($balance, 2) }}</strong><br><br>

          @if($sale->notes)
            {!! nl2br(e($sale->notes)) !!}
          @else
            El presente documento corresponde a la venta y contratación de los servicios
            detallados en este resumen. El cliente declara estar de acuerdo con el alcance,
            costos y plazos informados, así como con los Términos y Condiciones adjuntos.
          @endif
        </div>
      </td>

      <!-- Totales numéricos + firma -->
      <td style="width:75%; vertical-align:top; text-align:right;">

        <table class="totals">
          <tr>
            <td>SUB TOTAL:</td>
            <td class="right">S/ {{ number_format($sale->subtotal ?? 0, 2) }}</td>
          </tr>
          <tr>
            <td>IGV (18%):</td>
            <td class="right">
              @if(($sale->tax ?? 0) > 0)
                S/ {{ number_format($sale->tax, 2) }}
              @else
                –
              @endif
            </td>
          </tr>
          <tr class="grand-row">
            <td colspan="2">
              <div class="grand-box">
                <div class="grand-label">TOTAL:</div>
                <div class="grand-amount">S/ {{ number_format($total, 2) }}</div>
              </div>
            </td>
          </tr>
          <tr>
            <td>PAGOS REGISTRADOS:</td>
            <td class="right">S/ {{ number_format($paid, 2) }}</td>
          </tr>
          <tr>
            <td>SALDO PENDIENTE:</td>
            <td class="right strong">S/ {{ number_format($balance, 2) }}</td>
          </tr>
        </table>

        <div class="signature-box">
          <img class="signature-img" src="{{ public_path('imagenes/firmadaniel.png') }}" alt="Firma">
          <div class="signature-label">Firma</div>
          <div class="signature-line"></div>
          <div class="signature-name">Lorenzo Daniel S.O</div>
          <div class="signature-role">G. General CEO – Agencia DN</div>
        </div>

      </td>
    </tr>
  </table>

  <!-- ===== SALTO DE PÁGINA A TÉRMINOS ===== -->
  <div class="page-break"></div>

  <!-- ===== TÉRMINOS Y CONDICIONES (tipo contrato) ===== -->
  <div class="section-title">Términos y condiciones del servicio</div>
  <div class="section-divider"></div>

  <ol style="font-size:12.5px; padding-left:18px; margin-top:4px;">
    <li>
      <strong>Inicio del proyecto.</strong>
      El proyecto inicia con el pago inicial indicado en este documento.
      El saldo restante deberá cancelarse antes de la migración o entrega final
      del sitio web o servicio contratado.
    </li>
    <li>
      <strong>Plazos de entrega.</strong>
      Los tiempos estimados de entrega comienzan una vez recibido el pago inicial
      y toda la información necesaria (textos, imágenes, accesos, datos legales, etc.).
      Los plazos pueden variar según la complejidad del proyecto y la rapidez en la
      entrega de información por parte del cliente.
    </li>
    <li>
      <strong>Modificaciones.</strong>
      El cliente podrá solicitar cambios menores dentro del plazo acordado
      después de la entrega del diseño inicial. Cambios estructurales,
      nuevas secciones o funcionalidades fuera del alcance original
      se cotizarán por separado.
    </li>
    <li>
      <strong>Dominio y hosting.</strong>
      El cliente es responsable de contar con dominio y hosting activo, salvo
      que se contrate este servicio adicional con la agencia. La agencia podrá
      asesorar en la adquisición o administración de estos servicios.
    </li>
    <li>
      <strong>Contenido y derechos de autor.</strong>
      El cliente garantiza que cuenta con derechos de uso sobre todo el material
      entregado (logotipos, textos, imágenes, videos, etc.).
      La agencia no asumirá responsabilidad por el uso indebido de material
      protegido por derechos de autor aportado por el cliente.
    </li>
    <li>
      <strong>Cancelaciones.</strong>
      En caso de cancelación del proyecto por parte del cliente, el adelanto
      no es reembolsable. Si el proyecto se suspende por falta de información
      o respuesta por más de 7 días, podrá considerarse abandonado.
    </li>
    <li>
      <strong>Entrega final.</strong>
      Una vez aprobado el proyecto y cancelado el saldo pendiente, se realizará
      la migración al dominio del cliente y se entregarán los accesos acordados.
    </li>
    <li>
      <strong>Soporte y mantenimiento.</strong>
      La agencia podrá brindar soporte técnico por un periodo corto posterior
      a la publicación para correcciones menores. Cualquier mantenimiento
      adicional o cambios posteriores se cotizarán aparte o mediante un
      plan de soporte mensual.
    </li>
    <li>
      <strong>Propiedad del sitio y archivos.</strong>
      Una vez completado el pago total, el sitio web y sus archivos serán
      propiedad del cliente, sin perjuicio de librerías o componentes de
      terceros utilizados bajo sus respectivas licencias.
    </li>
  </ol>

  <!-- ===== PROCESO DE TRABAJO (resumen) ===== -->
  <div class="section-title" style="margin-top:18px;">Proceso de trabajo</div>
  <div class="section-divider"></div>

  <ol style="font-size:12.5px; padding-left:18px; margin-top:4px;">
    <li>
      <strong>Brief y recolección de información.</strong>
      Se completa un brief digital y se habilita una carpeta compartida
      para recibir logotipo, imágenes, datos de contacto, listado de
      productos (si aplica) y demás información necesaria.
    </li>
    <li>
      <strong>Diseño y desarrollo.</strong>
      Se prepara la estructura visual y funcional del sitio o servicio.
      Se comparte un enlace de previsualización y se coordinan reuniones
      para revisión de avances.
    </li>
    <li>
      <strong>Redacción y optimización.</strong>
      Se redactan y ajustan los textos según la identidad del cliente y
      se aplican optimizaciones básicas (copy, estructura y, en caso web,
      ajustes SEO iniciales).
    </li>
    <li>
      <strong>Aprobación y publicación.</strong>
      El cliente revisa la versión final, confirma su aprobación y, tras el
      pago del saldo pendiente, se publica o entrega el proyecto.
    </li>
    <li>
      <strong>Capacitación y entrega de accesos.</strong>
      Se puede brindar una sesión de capacitación y se entregan los accesos
      necesarios para la administración del sitio o sistema.
    </li>
  </ol>

  <!-- ===== FOOTER CONTACTO ===== -->
  <table class="footer">
    <tr>
      <td style="width: 50%;">
        <span class="pill">Contacto</span>
        <div style="margin-top: 8px;">
          Tel: +51 959 114 988<br>
          Web: agenciadn.net.pe<br>
          Dirección: Calle Robinson 113 – Surquillo – Lima
        </div>
      </td>
      <td style="width: 50%;">
        <span class="pill">Importante</span>
        <div style="margin-top: 8px;">
          Al firmar o aceptar digitalmente este documento, el cliente declara
          haber leído y aceptado los presentes términos y condiciones, así como
          el detalle económico de la venta.
        </div>
      </td>
    </tr>
  </table>
</body>
</html>
