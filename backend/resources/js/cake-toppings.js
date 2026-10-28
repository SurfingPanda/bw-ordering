import * as THREE from 'three';

// Original, procedural assets: no external downloads or texture requests.
// All geometry/material ownership passes to the cake preview for disposal.
export const flavorDecorations = {
    strawberry: 'fresh strawberries',
    chocolate: 'chocolate pieces',
    mocha: 'coffee beans',
    ube: 'ube cream rosettes',
    vanilla: 'vanilla blossoms',
    pandan: 'pandan leaves',
    'red velvet': 'red velvet crumbs',
};

export function addFlavorToppings(parent, flavor, radius, top, shape = 'Round') {
    const key = flavor.trim().toLowerCase();
    if (!flavorDecorations[key]) return '';
    const group = new THREE.Group();
    group.name = `flavor-${key}`;
    parent.add(group);
    const materials = new Map();
    const geometries = new Map();
    function material(color) {
        if (!materials.has(color)) materials.set(color, new THREE.MeshStandardMaterial({ color, roughness: 0.65 }));
        return materials.get(color);
    }
    function geometry(name, create) {
        if (!geometries.has(name)) geometries.set(name, create());
        return geometries.get(name);
    }
    function part(root, shape, color, position, scale = [1, 1, 1]) {
        const mesh = new THREE.Mesh(shape, material(color));
        mesh.position.set(...position);
        mesh.scale.set(...scale);
        mesh.castShadow = true;
        mesh.receiveShadow = true;
        root.add(mesh);
        return mesh;
    }
    const sphere = () => geometry('sphere', () => new THREE.SphereGeometry(1, 16, 12));
    const box = () => geometry('box', () => new THREE.BoxGeometry(1, 1, 1));

    function strawberry(root) {
        const profile = [
            [0, 0], [0.055, 0.035], [0.105, 0.105], [0.145, 0.19],
            [0.155, 0.26], [0.125, 0.315], [0.06, 0.34], [0, 0.34],
        ].map(([x, y]) => new THREE.Vector2(x, y));
        part(root, geometry('berry', () => new THREE.LatheGeometry(profile, 24)), '#dd2942', [0, 0, 0]);
        // Instanced golden seeds follow the berry surface, including its back.
        const seeds = new THREE.InstancedMesh(
            geometry('seed', () => new THREE.SphereGeometry(1, 6, 4)), material('#ffd388'), 40,
        );
        const transform = new THREE.Object3D();
        let index = 0;
        for (let row = 0; row < 5; row++) {
            const y = 0.075 + row * 0.05;
            const lower = profile.findIndex((point) => point.y >= y);
            const a = profile[lower - 1], b = profile[lower];
            const r = THREE.MathUtils.lerp(a.x, b.x, (y - a.y) / (b.y - a.y));
            for (let i = 0; i < 8; i++) {
                const angle = (i + row * 0.5) / 8 * Math.PI * 2;
                transform.position.set(Math.sin(angle) * (r + 0.002), y, Math.cos(angle) * (r + 0.002));
                transform.rotation.set(0, angle, 0);
                transform.scale.set(0.009, 0.014, 0.004);
                transform.updateMatrix();
                seeds.setMatrixAt(index++, transform.matrix);
            }
        }
        root.add(seeds);
        for (let i = 0; i < 5; i++) {
            const angle = i / 5 * Math.PI * 2;
            const leaf = part(root, sphere(), '#398347', [Math.sin(angle) * 0.055, 0.335, Math.cos(angle) * 0.055], [0.035, 0.012, 0.09]);
            leaf.rotation.y = angle;
        }
        part(root, geometry('stem', () => new THREE.CylinderGeometry(0.012, 0.015, 0.07, 8)), '#398347', [0, 0.37, 0]);
    }
    function rosette(root, color) {
        for (let i = 0; i < 7; i++) {
            const angle = i / 7 * Math.PI * 2;
            const petal = part(root, sphere(), color, [Math.cos(angle) * 0.065, 0.12, Math.sin(angle) * 0.065], [0.07, 0.115, 0.045]);
            petal.rotation.y = -angle;
            petal.rotation.z = 0.3;
        }
        part(root, sphere(), color, [0, 0.13, 0], [0.055, 0.11, 0.055]);
    }
    function topping(root) {
        if (key === 'strawberry') strawberry(root);
        if (key === 'chocolate') {
            const slab = part(root, box(), '#48271c', [0, 0.15, 0], [0.24, 0.3, 0.055]);
            slab.rotation.z = -0.25;
            for (const x of [-0.055, 0.055]) {
                for (const y of [0.1, 0.22]) part(root, box(), '#643b29', [x, y, 0.035], [0.09, 0.09, 0.025]);
            }
        }
        if (key === 'mocha') {
            rosette(root, '#c99b72');
            for (const side of [-1, 1]) {
                part(root, sphere(), '#553226', [side * 0.038, 0.23, 0], [0.038, 0.035, 0.095]);
            }
        }
        if (key === 'ube') rosette(root, '#8e59b8');
        if (key === 'vanilla') {
            for (let i = 0; i < 5; i++) {
                const angle = i / 5 * Math.PI * 2;
                const petal = part(root, sphere(), '#fff5d9', [Math.sin(angle) * 0.085, 0.04, Math.cos(angle) * 0.085], [0.05, 0.025, 0.1]);
                petal.rotation.y = angle;
            }
            part(root, sphere(), '#e9bb56', [0, 0.07, 0], [0.045, 0.035, 0.045]);
        }
        if (key === 'pandan') {
            for (const side of [-1, 1]) {
                const leaf = part(root, sphere(), '#438441', [side * 0.06, 0.17, 0], [0.035, 0.22, 0.012]);
                leaf.rotation.z = side * -0.5;
            }
        }
        if (key === 'red velvet') {
            rosette(root, '#fff2de');
            for (let i = 0; i < 9; i++) {
                const angle = i * 2.4;
                const crumb = part(root, box(), '#9e2937', [Math.sin(angle) * 0.09, 0.17 + (i % 3) * 0.02, Math.cos(angle) * 0.09], [0.025, 0.025, 0.025]);
                crumb.rotation.set(angle, angle * 0.7, 0.3);
            }
        }
    }
    // Keep the middle free for candles and occasion plaques on every tier size.
    const count = radius < 0.8 ? 5 : 8;
    for (let i = 0; i < count; i++) {
        const angle = (i + 0.5) / count * Math.PI * 2;
        const asset = new THREE.Group();
        const edge = shape === 'Round' ? 1 : 1 / Math.max(Math.abs(Math.cos(angle)), Math.abs(Math.sin(angle)));
        const stretch = shape === 'Rectangle' ? 1.35 : 1;
        asset.position.set(Math.cos(angle) * radius * 0.72 * edge * stretch, top, Math.sin(angle) * radius * 0.72 * edge);
        asset.rotation.y = -angle;
        asset.scale.setScalar(Math.min(1, radius / 0.9));
        group.add(asset);
        topping(asset);
    }
    return flavorDecorations[key];
}
