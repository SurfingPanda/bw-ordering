import * as THREE from 'three';

export const themeDecorations = {
    Anime: 'sample anime ninja', Cartoon: 'sample cartoon cat',
    Superhero: 'sample masked hero', Princess: 'gold crown',
    Space: 'space robot', Custom: 'custom-design star',
    Naruto: 'stylized Naruto topper', Luffy: 'stylized Luffy topper',
    Pikachu: 'stylized Pikachu topper', 'Hello Kitty': 'stylized Hello Kitty topper',
    SpongeBob: 'stylized SpongeBob topper',
    Mermaid: '3D mermaid character topper', Cocomelon: 'stylized Cocomelon topper',
    Unicorn: 'pastel unicorn topper', Dinosaur: 'little dinosaur topper',
};

// Original sample characters, not replicas of the customer's requested character.
export function addThemeTopper(parent, theme, radius, top) {
    if (!themeDecorations[theme]) return '';
    const group = new THREE.Group();
    group.name = `theme-${theme}`;
    const scale = Math.min(1, radius / 0.95);
    group.position.set(0, top, 0.12 * scale);
    group.scale.setScalar(scale);
    parent.add(group);
    const materials = new Map();
    function part(geometry, color, x, y, z, scale = [1, 1, 1]) {
        if (!materials.has(color)) materials.set(color, new THREE.MeshStandardMaterial({ color, roughness: 0.65 }));
        const item = new THREE.Mesh(geometry, materials.get(color));
        item.position.set(x, y, z); item.scale.set(...scale);
        item.castShadow = true; item.receiveShadow = true;
        group.add(item);
        return item;
    }
    const ball = (c, x, y, z, s) => part(new THREE.SphereGeometry(1, 20, 12), c, x, y, z, s);
    const box = (c, x, y, z, s) => part(new THREE.BoxGeometry(1, 1, 1), c, x, y, z, s);
    const cone = (c, x, y, z, r, h) => part(new THREE.ConeGeometry(r, h, 16), c, x, y, z);
    if (theme === 'Mermaid') {
        const skin = '#f3c7ac', hair = '#b95c76', sea = '#60bbad';
        // A seated figure with real volume, including a tapered, curled tail.
        const curve = new THREE.CatmullRomCurve3([
            new THREE.Vector3(0, 0.36, 0),
            new THREE.Vector3(-0.025, 0.2, 0.015),
            new THREE.Vector3(0.06, 0.11, 0.065),
            new THREE.Vector3(0.2, 0.13, 0.07),
            new THREE.Vector3(0.25, 0.22, 0.065),
        ]);
        const tail = new THREE.TubeGeometry(curve, 32, 0.1, 16, false);
        const positions = tail.attributes.position;
        for (let ring = 0; ring <= 32; ring++) {
            const t = ring / 32, center = curve.getPointAt(t);
            const taper = 1 - t * 0.75;
            for (let side = 0; side <= 16; side++) {
                const i = ring * 17 + side;
                positions.setXYZ(i,
                    center.x + (positions.getX(i) - center.x) * taper,
                    center.y + (positions.getY(i) - center.y) * taper,
                    center.z + (positions.getZ(i) - center.z) * taper);
            }
        }
        tail.computeVertexNormals();
        part(tail, sea, 0, 0, 0);
        ball(sea, 0, 0.35, 0, [0.1, 0.075, 0.095]);
        for (const side of [-1, 1]) {
            const fin = ball('#8ad5c7', 0.25 + side * 0.055, 0.275, 0.065, [0.055, 0.1, 0.025]);
            fin.rotation.z = side * -0.65;
        }
        ball(skin, 0, 0.465, 0, [0.09, 0.135, 0.075]);
        // Purple swimsuit bodice and shell details.
        ball('#b09bd8', 0, 0.44, 0.015, [0.095, 0.09, 0.073]);
        for (const side of [-1, 1]) {
            ball('#cfb6e6', side * 0.044, 0.49, 0.065, [0.047, 0.037, 0.018]);
            const arm = ball(skin, side * 0.11, 0.435, 0.005, [0.031, 0.115, 0.032]);
            arm.rotation.z = side * 0.2;
            ball(skin, side * 0.13, 0.335, 0.018, [0.034, 0.038, 0.034]);
        }
        ball(skin, 0, 0.59, 0, [0.04, 0.06, 0.04]);
        ball(hair, 0, 0.71, -0.025, [0.18, 0.19, 0.14]);
        ball(skin, 0, 0.71, 0.055, [0.142, 0.145, 0.105]);
        for (const side of [-1, 1]) {
            const lock = ball(hair, side * 0.137, 0.595, -0.015, [0.065, 0.2, 0.075]);
            lock.rotation.z = side * 0.15;
            ball('#344457', side * 0.052, 0.73, 0.151, [0.017, 0.025, 0.01]);
            ball('#fff7e8', side * 0.052 - 0.004, 0.739, 0.16, [0.005, 0.007, 0.003]);
            ball('#e9a0a1', side * 0.09, 0.68, 0.14, [0.025, 0.013, 0.006]);
        }
        const fringe = ball(hair, -0.04, 0.83, 0.1, [0.12, 0.045, 0.065]);
        fringe.rotation.z = -0.25;
        ball(skin, 0, 0.695, 0.16, [0.018, 0.02, 0.017]);
        const smile = part(new THREE.TorusGeometry(0.025, 0.005, 8, 16, Math.PI), '#ae6976', 0, 0.665, 0.15);
        smile.rotation.z = Math.PI;
        // Pearl hair accessory and a little shell at the base.
        ball('#f7e9ce', 0.12, 0.825, 0.095, [0.03, 0.03, 0.025]);
        ball('#dfc1df', -0.16, 0.03, 0.08, [0.07, 0.03, 0.055]);
    } else if (theme === 'Cocomelon') {
        ball('#70b953', 0, 0.3, 0, [0.27, 0.25, 0.18]);
        for (const x of [-0.18, -0.09, 0, 0.09, 0.18]) {
            ball('#98ce68', x, 0.32, -0.06, [0.025, 0.21 - Math.abs(x) * 0.3, 0.13]);
        }
        ball('#f6c5a2', 0, 0.3, 0.155, [0.215, 0.17, 0.045]);
        for (const side of [-1, 1]) {
            ball('#394550', side * 0.075, 0.33, 0.199, [0.018, 0.025, 0.01]);
            ball('#ec9896', side * 0.14, 0.265, 0.19, [0.033, 0.02, 0.008]);
            const antenna = box('#76b657', side * 0.07, 0.59, 0, [0.018, 0.16, 0.018]);
            antenna.rotation.z = -side * 0.35;
            ball(side < 0 ? '#e595ad' : '#9e93cd', side * 0.095, 0.67, 0, [0.045, 0.045, 0.045]);
        }
        const smile = part(new THREE.TorusGeometry(0.043, 0.009, 8, 20, Math.PI), '#8d5649', 0, 0.26, 0.2);
        smile.rotation.z = Math.PI;
    } else if (theme === 'Unicorn') {
        ball('#fff4e8', 0, 0.23, 0, [0.16, 0.2, 0.12]);
        ball('#fff4e8', 0, 0.51, 0, [0.18, 0.17, 0.13]);
        ball('#f3c6d8', 0, 0.45, 0.12, [0.11, 0.07, 0.055]);
        cone('#e5bd66', 0, 0.77, 0.02, 0.045, 0.25);
        for (const side of [-1, 1]) {
            cone('#fff4e8', side * 0.12, 0.68, 0, 0.055, 0.15);
            ball('#3e405b', side * 0.085, 0.53, 0.116, [0.016, 0.024, 0.01]);
            ball('#d4b7df', side * 0.09, 0.05, 0.04, [0.065, 0.05, 0.07]);
        }
        ['#edb2d1', '#b8a5d9', '#97cfce', '#edcf8e'].forEach((color, i) => {
            ball(color, -0.14, 0.62 - i * 0.1, -0.05, [0.075, 0.1, 0.09]);
        });
    } else if (theme === 'Dinosaur') {
        ball('#87b985', 0, 0.23, 0, [0.18, 0.21, 0.14]);
        ball('#87b985', 0, 0.5, 0.04, [0.2, 0.17, 0.16]);
        ball('#c9dd9d', 0, 0.24, 0.12, [0.115, 0.145, 0.03]);
        const tail = cone('#87b985', 0, 0.17, -0.2, 0.1, 0.28);
        tail.rotation.x = -Math.PI / 2;
        for (let i = 0; i < 4; i++) cone('#e6b16c', 0, 0.65 - i * 0.1, -0.05 - i * 0.045, 0.055, 0.11);
        for (const side of [-1, 1]) {
            ball('#87b985', side * 0.12, 0.05, 0.04, [0.08, 0.05, 0.09]);
            ball('#87b985', side * 0.18, 0.28, 0.06, [0.05, 0.09, 0.045]);
            ball('#354a40', side * 0.09, 0.53, 0.18, [0.018, 0.024, 0.012]);
        }
    } else if (theme === 'Pikachu') {
        const yellow = '#f4ce39';
        ball(yellow, 0, 0.22, 0, [0.16, 0.2, 0.12]);
        ball(yellow, 0, 0.49, 0, [0.19, 0.17, 0.14]);
        for (const side of [-1, 1]) {
            const ear = ball(yellow, side * 0.13, 0.7, 0, [0.045, 0.16, 0.035]);
            ear.rotation.z = side * -0.25;
            const tip = ball('#3f342e', side * 0.16, 0.81, 0, [0.039, 0.06, 0.032]);
            tip.rotation.z = side * -0.25;
            ball(yellow, side * 0.1, 0.04, 0.04, [0.07, 0.04, 0.09]);
            ball(yellow, side * 0.16, 0.24, 0.035, [0.045, 0.1, 0.045]);
            ball('#322c27', side * 0.07, 0.51, 0.13, [0.022, 0.027, 0.015]);
            ball('#fff9e6', side * 0.074, 0.52, 0.143, [0.007, 0.008, 0.004]);
            ball('#df5043', side * 0.13, 0.45, 0.115, [0.037, 0.03, 0.018]);
        }
        ball('#322c27', 0, 0.465, 0.145, [0.016, 0.01, 0.009]);
        const tail = new THREE.Shape();
        tail.moveTo(0, 0); tail.lineTo(0.06, 0.13); tail.lineTo(0.02, 0.16);
        tail.lineTo(0.15, 0.32); tail.lineTo(0.24, 0.24); tail.lineTo(0.12, 0.14);
        tail.lineTo(0.15, 0.1); tail.lineTo(0.05, 0); tail.closePath();
        part(new THREE.ExtrudeGeometry(tail, { depth: 0.03, bevelEnabled: false }), yellow, 0.08, 0.12, -0.13);
    } else if (theme === 'SpongeBob') {
        box('#efcb40', 0, 0.43, 0, [0.4, 0.39, 0.16]);
        box('#fff9eb', 0, 0.225, 0, [0.4, 0.05, 0.16]);
        box('#916039', 0, 0.17, 0, [0.4, 0.07, 0.16]);
        for (const side of [-1, 1]) {
            box('#fff9eb', side * 0.11, 0.09, 0, [0.035, 0.1, 0.035]);
            ball('#302c2c', side * 0.12, 0.035, 0.02, [0.08, 0.035, 0.06]);
            box('#efcb40', side * 0.23, 0.3, 0, [0.03, 0.17, 0.03]);
            ball('#fff9eb', side * 0.085, 0.49, 0.09, [0.08, 0.085, 0.035]);
            ball('#76b9e0', side * 0.07, 0.49, 0.12, [0.037, 0.043, 0.015]);
            ball('#302c2c', side * 0.07, 0.49, 0.133, [0.018, 0.024, 0.008]);
            box('#fff9eb', side * 0.025, 0.345, 0.09, [0.04, 0.045, 0.015]);
            ball('#ccaa32', side * 0.15, 0.59, 0.082, [0.023, 0.018, 0.008]);
            ball('#ccaa32', side * 0.16, 0.32, 0.082, [0.019, 0.025, 0.008]);
        }
        ball('#efcb40', 0, 0.42, 0.14, [0.03, 0.045, 0.055]);
        box('#d04746', 0, 0.205, 0.09, [0.025, 0.065, 0.015]);
    } else if (theme === 'Princess') {
        part(new THREE.CylinderGeometry(0.22, 0.2, 0.16, 40, 1, true), '#e9b84b', 0, 0.1, 0);
        for (let i = 0; i < 7; i++) {
            const a = i / 7 * Math.PI * 2;
            cone('#e9b84b', Math.cos(a) * 0.19, 0.23, Math.sin(a) * 0.19, 0.07, 0.22);
            ball('#e997bb', Math.cos(a) * 0.19, 0.35, Math.sin(a) * 0.19, [0.035, 0.035, 0.035]);
        }
    } else if (theme === 'Custom') {
        const star = new THREE.Shape();
        for (let i = 0; i < 10; i++) {
            const a = Math.PI / 2 + i * Math.PI / 5, r = i % 2 ? 0.1 : 0.22;
            if (!i) star.moveTo(Math.cos(a) * r, Math.sin(a) * r);
            else star.lineTo(Math.cos(a) * r, Math.sin(a) * r);
        }
        star.closePath();
        part(new THREE.CylinderGeometry(0.015, 0.015, 0.35, 10), '#dbb35c', 0, 0.175, 0);
        part(new THREE.ExtrudeGeometry(star, { depth: 0.05, bevelEnabled: false }), '#a98bd4', 0, 0.48, -0.025);
    } else {
        const robot = theme === 'Space', cat = ['Cartoon', 'Hello Kitty'].includes(theme), hero = theme === 'Superhero';
        const kitty = theme === 'Hello Kitty', luffy = theme === 'Luffy';
        const suit = robot ? '#c2d7e2' : kitty || luffy ? '#e45f77' : cat ? '#fff1df' : hero ? '#547ac7' : '#ee934e';
        const skin = robot ? '#dce8ec' : cat ? '#fff1df' : '#f2c6a5';
        for (const side of [-1, 1]) {
            ball(suit, side * 0.09, 0.06, 0.025, [0.07, 0.06, 0.085]);
            ball(suit, side * 0.17, 0.25, 0, [0.055, 0.12, 0.055]);
        }
        ball(suit, 0, 0.24, 0, [0.14, 0.19, 0.1]);
        if (robot) box(skin, 0, 0.53, 0, [0.32, 0.26, 0.23]);
        else ball(skin, 0, 0.53, 0, [0.19, 0.18, 0.145]);
        if (cat) {
            for (const side of [-1, 1]) {
                cone(skin, side * 0.13, 0.72, 0, 0.075, 0.16);
                cone('#efb2ba', side * 0.13, 0.72, 0.035, 0.042, 0.09);
                ball('#ee9caa', side * 0.12, 0.49, 0.123, [0.035, 0.018, 0.012]);
            }
            ball(kitty ? '#efc75d' : '#d997a7', 0, 0.48, 0.145, [0.023, 0.016, 0.012]);
            ball('#9ebbd7', 0, 0.35, 0.105, [0.075, 0.025, 0.02]);
            if (kitty) {
                for (const x of [0.075, 0.155]) ball('#e45f77', x, 0.68, 0.12, [0.055, 0.045, 0.025]);
                ball('#d44763', 0.115, 0.68, 0.145, [0.025, 0.025, 0.02]);
                for (const side of [-1, 1]) for (let i = 0; i < 3; i++) {
                    box('#43373a', side * 0.17, 0.46 + i * 0.035, 0.12, [0.08, 0.009, 0.009]);
                }
            }
        } else if (robot) {
            box('#30475c', 0, 0.54, 0.12, [0.26, 0.13, 0.015]);
            part(new THREE.CylinderGeometry(0.01, 0.01, 0.09, 8), '#30475c', 0, 0.71, 0);
            ball('#ee9b59', 0, 0.77, 0, [0.035, 0.035, 0.035]);
            box('#ee9b59', 0, 0.26, 0.1, [0.08, 0.06, 0.015]);
        } else if (hero) {
            box('#293d7e', 0, 0.56, 0.13, [0.31, 0.075, 0.025]);
            const cape = box('#d85c72', 0, 0.23, -0.115, [0.29, 0.35, 0.025]);
            cape.rotation.x = -0.2;
            ball('#edc25e', 0, 0.26, 0.095, [0.055, 0.06, 0.015]);
        } else if (luffy) {
            part(new THREE.CylinderGeometry(0.27, 0.27, 0.035, 32), '#e7c279', 0, 0.68, 0);
            ball('#e7c279', 0, 0.7, 0, [0.18, 0.11, 0.16]);
            part(new THREE.CylinderGeometry(0.181, 0.181, 0.04, 32), '#cf5253', 0, 0.71, 0);
            box('#f2c6a5', 0, 0.26, 0.1, [0.065, 0.18, 0.02]);
            box('#5279b9', 0, 0.12, 0, [0.22, 0.08, 0.15]);
        } else {
            for (let i = -2; i <= 2; i++) {
                const spike = cone(theme === 'Naruto' ? '#efc345' : '#303345', i * 0.065, 0.72 - Math.abs(i) * 0.02, -0.015, 0.07, 0.19);
                spike.rotation.z = -i * 0.2;
            }
            box('#3c5273', 0, 0.635, 0.12, [0.31, 0.07, 0.025]);
            box('#c3d0d8', 0, 0.635, 0.14, [0.12, 0.05, 0.015]);
            box('#3c5273', 0, 0.33, 0.08, [0.22, 0.05, 0.04]);
            if (theme === 'Naruto') for (const side of [-1, 1]) for (let i = 0; i < 3; i++) {
                box('#a87962', side * 0.115, 0.46 + i * 0.018, 0.125, [0.045, 0.006, 0.006]);
            }
        }
        for (const side of [-1, 1]) ball(robot ? '#85e1db' : '#27354a', side * 0.065, 0.55, 0.145, [0.018, 0.024, 0.012]);
    }
    return themeDecorations[theme];
}
