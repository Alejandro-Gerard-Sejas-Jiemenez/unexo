// Spec 012. Ejecutar: node --test tests/js/paginar.test.mjs
import assert from 'node:assert/strict';
import { test } from 'node:test';
import { TAMANOS, mostrarControl, paginar } from '../../resources/js/spa/lib/paginar.js';

const filas = Array.from({ length: 23 }, (_, i) => i + 1);

test('CA-01: 23 filas se reparten en 10, 10 y 3 sin repetir ni faltar', () => {
    const paginas = [1, 2, 3].map((p) => paginar(filas, p));

    assert.deepEqual(paginas.map((p) => p.visibles.length), [10, 10, 3]);
    assert.deepEqual(paginas.flatMap((p) => p.visibles), filas);
    assert.deepEqual(paginas[2].meta, { current_page: 3, last_page: 3, per_page: 10, total: 23, from: 21, to: 23 });
});

test('CA-02: con 10 filas o menos hay una sola página', () => {
    assert.equal(paginar(filas.slice(0, 10), 1).meta.last_page, 1);
    assert.equal(paginar(filas.slice(0, 3), 1).meta.last_page, 1);
});

test('CA-03: una página inexistente muestra la última y una lista vacía la primera', () => {
    assert.equal(paginar(filas, 9).meta.current_page, 3);
    assert.equal(paginar(filas, 0).meta.current_page, 1);

    const vacia = paginar([], 4);
    assert.deepEqual(vacia.visibles, []);
    assert.equal(vacia.meta.current_page, 1);
    assert.equal(vacia.meta.last_page, 1);
});

test('CA-04: la lista original no cambia', () => {
    const original = [...filas];
    paginar(filas, 2);

    assert.deepEqual(filas, original);
});

test('CA-02: el control se oculta con 5 registros o menos', () => {
    assert.equal(mostrarControl(paginar(filas.slice(0, 5), 1).meta), false);
    assert.equal(mostrarControl(paginar(filas.slice(0, 7), 1).meta), true);
    assert.equal(mostrarControl(paginar(filas, 1, 50).meta), true);
    assert.equal(mostrarControl(undefined), false);
});

test('CA-05: se puede elegir cuántas filas ver', () => {
    assert.deepEqual(TAMANOS, [5, 10, 25, 50]);
    assert.equal(paginar(filas, 1, 5).meta.last_page, 5);
    assert.equal(paginar(filas, 1, 25).meta.last_page, 1);
    assert.equal(paginar(filas, 1, 50).visibles.length, 23);
});
