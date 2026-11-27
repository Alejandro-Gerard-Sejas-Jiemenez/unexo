// Spec 017. Ejecutar: node --test tests/js/pagos.test.mjs
import assert from 'node:assert/strict';
import { test } from 'node:test';
import { dividirEnCuotas, resumirPagos } from '../../resources/js/spa/lib/pagos.js';

const cargo = (monto, estado, numero = null) => ({ monto, estado, numero_cuota: numero });

test('CA-01: sin cargos usa el precio del programa', () => {
    assert.deepEqual(resumirPagos([], 950), { total: 950, pagado: 0, pendiente: 950, proximo: null, situacion: 'sin_cargos' });
});

test('CA-01: sin cargos ni precio no hay importe definido', () => {
    const r = resumirPagos([], null);

    assert.equal(r.total, null);
    assert.equal(r.pendiente, null);
    assert.equal(r.situacion, 'sin_cargos');
});

test('CA-01: con cuotas pendientes muestra la próxima; una rechazada se sigue debiendo', () => {
    const r = resumirPagos([cargo(100.1, 'pendiente', 3), cargo(100.1, 'aprobada', 1), cargo(100.1, 'rechazada', 2)], 950);

    assert.equal(r.total, 300.3);
    assert.equal(r.pagado, 100.1);
    assert.equal(r.pendiente, 200.2);
    assert.equal(r.proximo.numero_cuota, 2);
    assert.equal(r.situacion, 'por_pagar');
});

test('CA-01: con todo pagado no queda nada y los cancelados no cuentan', () => {
    const r = resumirPagos([cargo(152, 'aprobada'), cargo(999, 'cancelada')], 950);

    assert.deepEqual([r.total, r.pagado, r.pendiente, r.proximo, r.situacion], [152, 152, 0, null, 'pagado']);
});

test('CA-02: las cuotas suman exacto y la última absorbe el redondeo', () => {
    assert.deepEqual(dividirEnCuotas(1000, 3), [333.33, 333.33, 333.34]);
    assert.deepEqual(dividirEnCuotas('800', 2), [400, 400]);
    assert.deepEqual(dividirEnCuotas(1500.5, 6), [250.08, 250.08, 250.08, 250.08, 250.08, 250.1]);
});

test('CA-02: sin un monto o una cantidad válidos no hay vista previa', () => {
    for (const [total, n] of [['', 2], [0, 2], [100, 1], [100, 37], [100, ''], [0.01, 2]]) {
        assert.deepEqual(dividirEnCuotas(total, n), []);
    }
});
