<?php

class CalculadoraAmortizacion
{
    private const IVA = 0.16;

    public static function calcularPMT(float $tasa_mensual, int $plazo, float $monto): float
    {
        if ($tasa_mensual == 0) {
            return round($monto / $plazo, 6);
        }
        $pmt = $monto * $tasa_mensual / (1 - pow(1 + $tasa_mensual, -$plazo));
        return round($pmt, 6);
    }

    public static function generarPeriodos(array $p): array
    {
        $monto       = (float) $p['monto_credito'];
        $plazo       = (int)   $p['plazo_meses'];
        $tasa_anual  = (float) $p['tasa_anual'];
        $fecha_inicio = new DateTime($p['fecha_inicio']);

        $tasa_mensual = $tasa_anual / 12;
        $pago_mensual = self::calcularPMT($tasa_mensual, $plazo, $monto);

        $periodos           = [];
        $saldo              = $monto;
        $pago_anticipado_anterior = 0;

        for ($i = 1; $i <= $plazo; $i++) {
            $fecha_corte = (clone $fecha_inicio)->modify("+{$i} months");
            // Normalizar a 30 días por periodo como dice el spec
            $fecha_corte = (clone $fecha_inicio);
            $dias_totales = $i * 30;
            $fecha_corte->modify("+{$dias_totales} days");

            $fecha_vencimiento = (clone $fecha_corte)->modify('+10 days');
            $fecha_inicio_mes  = (clone $fecha_corte)->modify('first day of this month');

            $interes_ordinario = round($saldo * $tasa_mensual, 6);
            $iva_interes       = round($interes_ordinario * self::IVA, 6);

            $es_ultimo = $saldo + $interes_ordinario <= $pago_mensual + 0.01;

            if ($es_ultimo) {
                $pago_capital = round($saldo, 6);
            } else {
                $pago_capital = round($pago_mensual - $interes_ordinario, 6);
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

    public static function calcularTotales(array $periodos): array
    {
        $total_intereses = 0;
        $total_iva       = 0;
        $total_a_pagar   = 0;

        foreach ($periodos as $p) {
            $total_intereses += $p['interes_ordinario'];
            $total_iva       += $p['iva_interes'];
            $total_a_pagar   += $p['pago_capital'] + $p['interes_ordinario'] + $p['iva_interes'];
        }

        return [
            'total_intereses' => round($total_intereses, 6),
            'total_iva'       => round($total_iva, 6),
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
