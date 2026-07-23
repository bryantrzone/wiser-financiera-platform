<?php

class CalculadoraAmortizacion
{
    private const IVA = 0.16;

    /**
     * Pago fijo (sistema francés). Si se pasa $tasa_iva, el IVA sobre el interés
     * queda incorporado dentro del pago usando la tasa efectiva tasa_mensual*(1+IVA),
     * de modo que el pago integrado (capital + interés + IVA) es constante en todos
     * los periodos.
     */
    public static function calcularPMT(float $tasa_mensual, int $plazo, float $monto, float $tasa_iva = 0.0): float
    {
        $tasa_efectiva = $tasa_mensual * (1 + $tasa_iva);
        if ($tasa_efectiva == 0) {
            return round($monto / $plazo, 6);
        }
        $pmt = $monto * $tasa_efectiva / (1 - pow(1 + $tasa_efectiva, -$plazo));
        return round($pmt, 6);
    }

    public static function generarPeriodos(array $p): array
    {
        $monto        = (float) $p['monto_credito'];
        $plazo        = (int)   $p['plazo_meses'];
        $tasa_anual   = (float) $p['tasa_anual'];
        $fecha_inicio = new DateTime($p['fecha_inicio']);
        $comision_pct = (float) ($p['comision_apertura_pct'] ?? 0);
        $tasa_iva     = isset($p['aplicar_iva']) && (int) $p['aplicar_iva'] === 0 ? 0.0 : self::IVA;

        $tasa_mensual = $tasa_anual / 12;

        // La comisión de apertura se cobra por separado: la amortización corre
        // sobre el monto completo del crédito (igual que el Excel: PMT(...,-monto)).
        $comision_monto = $comision_pct > 0
            ? round($monto * $comision_pct, 6)
            : 0;

        // Pago fijo con IVA incorporado (constante en todos los periodos).
        $pago_mensual = self::calcularPMT($tasa_mensual, $plazo, $monto, $tasa_iva);

        $periodos                 = [];
        $saldo                    = $monto;
        $pago_anticipado_anterior = 0;

        for ($i = 1; $i <= $plazo; $i++) {
            $fecha_corte = (clone $fecha_inicio)->modify("+{$i} months");
            // Normalizar a 30 días por periodo como dice el spec
            $fecha_corte = (clone $fecha_inicio);
            $dias_totales = $i * 30;
            $fecha_corte->modify("+{$dias_totales} days");

            // La fecha de pago cae el mismo día del mes que la fecha de inicio
            // (inicio 23 → pagos el 23 de cada mes), ajustando al último día
            // disponible cuando el mes destino no tiene ese día (ej. 31 → 28/feb).
            $fecha_vencimiento = self::sumarMeses($fecha_inicio, $i);
            $fecha_inicio_mes  = (clone $fecha_corte)->modify('first day of this month');

            $interes_ordinario = round($saldo * $tasa_mensual, 6);
            $iva_interes       = round($interes_ordinario * $tasa_iva, 6);

            // Último periodo: el capital liquida el saldo restante (absorbe el redondeo).
            $es_ultimo = ($i === $plazo)
                || ($saldo + $interes_ordinario + $iva_interes <= $pago_mensual + 0.01);

            if ($es_ultimo) {
                $pago_capital = round($saldo, 6);
            } else {
                // capital = pago fijo − interés − IVA  → pago integrado constante
                $pago_capital = round($pago_mensual - $interes_ordinario - $iva_interes, 6);
            }

            $pago_calculado  = round($pago_capital + $interes_ordinario + $iva_interes, 6);
            $pago_integrado  = round($pago_calculado - $pago_anticipado_anterior, 6);

            $periodos[] = [
                'periodo'           => $i,
                'fecha_inicio_mes'  => $fecha_inicio_mes->format('Y-m-d'),
                'fecha_vencimiento' => $fecha_vencimiento->format('Y-m-d'),
                'fecha_corte'       => $fecha_corte->format('Y-m-d'),
                'dias'              => 30,
                'saldo_insoluto'    => round($saldo, 6),
                'pago_capital'      => $pago_capital,
                'interes_ordinario' => $interes_ordinario,
                'iva_interes'       => $iva_interes,
                'importe_comision'  => 0,
                'excedente_pagado'  => 0,
                'pago_anticipado'   => 0,
                'pago_calculado'    => $pago_calculado,
                'pago_integrado'    => $pago_integrado,
            ];

            $saldo = round($saldo - $pago_capital - $pago_anticipado_anterior, 6);
            $pago_anticipado_anterior = 0;

            if ($saldo <= 0.01) {
                break;
            }
        }

        return $periodos;
    }

    /**
     * Suma $meses meses a $base conservando el día del mes. Si el mes destino no
     * tiene ese día (ej. 31 en febrero), ajusta al último día de ese mes. Evita el
     * desbordamiento nativo de PHP (31/ene +1 mes = 03/mar).
     */
    private static function sumarMeses(DateTime $base, int $meses): DateTime
    {
        $dia       = (int) $base->format('d');
        $resultado = (clone $base)->modify('first day of this month')->modify("+{$meses} months");
        $dias_mes  = (int) $resultado->format('t');
        $resultado->setDate(
            (int) $resultado->format('Y'),
            (int) $resultado->format('n'),
            min($dia, $dias_mes)
        );
        return $resultado;
    }

    public static function calcularTotales(array $periodos): array
    {
        $total_intereses = 0;
        $total_iva       = 0;
        $total_comision  = 0;
        $total_a_pagar   = 0;

        foreach ($periodos as $p) {
            $total_intereses += $p['interes_ordinario'];
            $total_iva       += $p['iva_interes'];
            $total_comision  += $p['importe_comision'];
            $total_a_pagar   += $p['pago_calculado'];
        }

        return [
            'total_intereses' => round($total_intereses, 6),
            'total_iva'       => round($total_iva, 6),
            'total_comision'  => round($total_comision, 6),
            'total_a_pagar'   => round($total_a_pagar, 6),
        ];
    }

    public static function generarCreditoNo(PDO $conn): string
    {
        $stmt = $conn->query("SELECT COUNT(*) FROM cotizaciones");
        $count = (int) $stmt->fetchColumn();
        return 'CS' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }

    public static function formatearFechaMx(string $fecha): string
    {
        return date('d/m/Y', strtotime($fecha));
    }

    public static function formatearMoneda(float $valor): string
    {
        return '$' . number_format($valor, 2);
    }
}
