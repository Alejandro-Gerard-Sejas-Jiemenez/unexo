// Spec 015. Ejecutar: node --test tests/js/horarios.test.mjs
import assert from 'node:assert/strict';
import { test } from 'node:test';
import { agregarBloque } from '../../resources/js/spa/lib/horarios.js';

const h = (dia, inicio = '19:00', fin = '21:00') => ({ dia_semana: String(dia), hora_inicio: inicio, hora_fin: fin });
const bloque = (dias, inicio = '19:00', fin = '21:00') => ({ dias, hora_inicio: inicio, hora_fin: fin });

test('CA-01: varios días con una sola hora agregan un horario por día, en orden', () => {
    const r = agregarBloque([], bloque([5, 1, 3]));

    assert.equal(r.error, null);
    assert.deepEqual(r.horarios, [h(1), h(3), h(5)]);
});

test('CA-02: un bloque inválido muestra el motivo y no agrega nada', () => {
    const actuales = [h(1)];
    const casos = [
        [bloque([]), 'Marca al menos un día.'],
        [bloque([2], '', '21:00'), 'Completa la hora de inicio y de fin.'],
        [bloque([2], '21:00', '19:00'), 'La hora de fin debe ser posterior a la de inicio.'],
        [bloque([2], '19:00', '19:00'), 'La hora de fin debe ser posterior a la de inicio.'],
        [bloque([2, 1], '20:00', '22:00'), 'En lunes, los horarios se solapan.'],
    ];

    for (const [entrada, motivo] of casos) {
        const r = agregarBloque(actuales, entrada);
        assert.equal(r.error, motivo);
        assert.deepEqual(r.horarios, actuales);
    }

    const lleno = [1, 2, 3, 4, 5, 6, 7].flatMap((d) => [h(d, '08:00', '09:00'), h(d, '10:00', '11:00')]);
    const r = agregarBloque(lleno, bloque([1], '12:00', '13:00'));
    assert.equal(r.error, 'Un grupo admite hasta 14 horarios.');
    assert.equal(r.horarios.length, 14);
});

test('CA-02: un horario idéntico no se repite y los que solo se tocan se aceptan', () => {
    assert.deepEqual(agregarBloque([h(1)], bloque([1, 3])).horarios, [h(1), h(3)]);

    const r = agregarBloque([h(1, '17:00', '19:00')], bloque([1]));
    assert.equal(r.error, null);
    assert.deepEqual(r.horarios, [h(1, '17:00', '19:00'), h(1)]);
});
