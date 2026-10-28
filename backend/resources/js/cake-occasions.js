import * as THREE from 'three';

export const occasionDecorations = {
    birthday: 'birthday candles', wedding: 'gold wedding rings',
    anniversary: 'heart topper', graduation: 'graduation cap and diploma',
    'baby shower': 'teddy bear', other: 'gold star topper',
};

// Original mesh assets, owned and disposed by the preview.
export function addOccasionTopper(parent, occasion, radius, top) {
    if (!occasion.trim()) return '';
    const key = occasion.trim().toLowerCase();
    const group = new THREE.Group();
    group.name = `occasion-${key}`;
    group.position.y = top;
    group.scale.setScalar(Math.min(1, radius / 0.95));
    parent.add(group);
    const materials = new Map();
    function part(geometry, color, x, y, z, scale = [1, 1, 1]) {
        if (!materials.has(color)) materials.set(color, new THREE.MeshStandardMaterial({ color, roughness: color === '#dbb35c' ? 0.3 : 0.7, metalness: color === '#dbb35c' ? 0.6 : 0 }));
        const mesh = new THREE.Mesh(geometry, materials.get(color));
        mesh.position.set(x, y, z); mesh.scale.set(...scale);
        mesh.castShadow = true; mesh.receiveShadow = true;
        group.add(mesh);
        return mesh;
    }
    const ball = (color, x, y, z, scale) => part(new THREE.SphereGeometry(1, 20, 14), color, x, y, z, scale);
    const rod = (r, h, color, x, y, z) => part(new THREE.CylinderGeometry(r, r, h, 20), color, x, y, z);
    const gold = '#dbb35c';
    if (key === 'birthday') {
        for (let i = -1; i <= 1; i++) {
            const h = i === 0 ? 0.55 : 0.4;
            rod(0.04, h, ['#91b9df', '#f49aaf', '#ac9ad2'][i + 1], i * 0.22, h / 2, 0);
            rod(0.008, 0.04, '#45352c', i * 0.22, h + 0.02, 0);
            ball('#ffbd48', i * 0.22, h + 0.09, 0, [0.045, 0.08, 0.045]);
        }
    } else if (key === 'wedding') {
        for (const x of [-0.13, 0.13]) {
            const ring = part(new THREE.TorusGeometry(0.2, 0.035, 12, 40), gold, x, 0.23, x * 0.3);
            ring.rotation.y = x * 1.5;
        }
        part(new THREE.OctahedronGeometry(0.085), '#e7f5ff', 0.13, 0.49, 0.04);
    } else if (key === 'graduation') {
        rod(0.18, 0.17, '#24314e', 0, 0.095, 0);
        const cap = part(new THREE.BoxGeometry(0.57, 0.045, 0.48), '#24314e', 0, 0.2, 0);
        cap.rotation.y = 0.2;
        const cord = rod(0.012, 0.25, gold, 0.12, 0.24, 0);
        cord.rotation.z = Math.PI / 2;
        rod(0.017, 0.19, gold, 0.245, 0.15, 0);
        const diploma = rod(0.055, 0.39, '#fff2d7', 0, 0.06, 0.28);
        diploma.rotation.z = Math.PI / 2;
        const ribbon = rod(0.06, 0.04, '#b7384b', 0, 0.06, 0.28);
        ribbon.rotation.z = Math.PI / 2;
    } else if (key === 'baby shower') {
        const fur = '#c99062';
        ball(fur, 0, 0.22, 0, [0.16, 0.21, 0.12]);
        ball(fur, 0, 0.48, 0, [0.18, 0.17, 0.14]);
        for (const side of [-1, 1]) {
            ball(fur, side * 0.15, 0.61, 0, [0.075, 0.075, 0.045]);
            ball('#f4d4af', side * 0.15, 0.61, 0.035, [0.04, 0.045, 0.015]);
            ball(fur, side * 0.18, 0.25, 0, [0.07, 0.12, 0.07]);
            ball(fur, side * 0.11, 0.07, 0.07, [0.085, 0.07, 0.1]);
            ball('#37271e', side * 0.065, 0.5, 0.13, [0.016, 0.018, 0.012]);
        }
        ball('#f4d4af', 0, 0.43, 0.13, [0.085, 0.06, 0.035]);
        ball('#37271e', 0, 0.45, 0.165, [0.025, 0.018, 0.012]);
    } else {
        const shape = new THREE.Shape();
        if (key === 'anniversary') {
            shape.moveTo(0, -0.2);
            shape.bezierCurveTo(-0.5, 0.08, -0.2, 0.4, 0, 0.18);
            shape.bezierCurveTo(0.2, 0.4, 0.5, 0.08, 0, -0.2);
        } else {
            for (let i = 0; i < 10; i++) {
                const a = Math.PI / 2 + i * Math.PI / 5;
                const r = i % 2 ? 0.12 : 0.27;
                if (i === 0) shape.moveTo(Math.cos(a) * r, Math.sin(a) * r);
                else shape.lineTo(Math.cos(a) * r, Math.sin(a) * r);
            }
            shape.closePath();
        }
        rod(0.017, 0.35, gold, 0, 0.175, 0);
        part(new THREE.ExtrudeGeometry(shape, { depth: 0.05, bevelEnabled: true, bevelThickness: 0.015, bevelSize: 0.015, bevelSegments: 2, steps: 1 }), key === 'anniversary' ? '#df637c' : gold, 0, 0.47, -0.025);
    }
    return occasionDecorations[key] || occasionDecorations.other;
}
