import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import { addFlavorToppings } from './cake-toppings';
import { addOccasionTopper } from './cake-occasions';
import { addThemeTopper } from './cake-themes';

const host = document.getElementById('cake-3d');
if (host) {
    try {
        mountCakePreview(host);
    } catch (error) {
        // The form and its SVG preview remain usable without WebGL.
        console.warn('3D cake preview unavailable.', error);
    }
}

function mountCakePreview(host) {
    const form = document.getElementById('cake-form');
    const status = document.getElementById('cake-preview-status');
    const fallback = document.getElementById('cake-preview');
    const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.shadowMap.enabled = true;
    renderer.shadowMap.type = THREE.PCFSoftShadowMap;
    renderer.setClearColor(0x000000, 0);
    const canvas = renderer.domElement;
    canvas.style.cssText = 'display:block;width:100%;height:100%;touch-action:pan-y;cursor:grab';
    canvas.tabIndex = 0;
    canvas.setAttribute('role', 'img');
    canvas.setAttribute('aria-label', '3D cake. Drag to rotate; pinch to zoom. Use arrow keys to rotate, plus and minus to zoom, or Home to reset.');
    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(38, 1, 0.1, 50);
    const controls = new OrbitControls(camera, canvas);
    canvas.style.touchAction = 'pan-y';
    controls.enablePan = false;
    controls.enableZoom = true;
    controls.minDistance = 5;
    controls.maxDistance = 13;
    controls.minPolarAngle = 0.2;
    controls.maxPolarAngle = Math.PI / 2 - 0.05;
    // Leave one-finger vertical gestures available for scrolling the form.
    controls.touches.ONE = THREE.TOUCH.ROTATE;
    scene.add(new THREE.HemisphereLight(0xffffff, 0xc9a88c, 2.8));
    const light = new THREE.DirectionalLight(0xfff3e0, 3.2);
    light.position.set(-3, 7, 5);
    light.castShadow = true;
    light.shadow.mapSize.set(1024, 1024);
    light.shadow.normalBias = 0.03;
    scene.add(light);
    const fill = new THREE.DirectionalLight(0xffffff, 1.5);
    fill.position.set(4, 3, -4);
    scene.add(fill);
    const cake = new THREE.Group();
    scene.add(cake);
    const ground = new THREE.Mesh(new THREE.PlaneGeometry(30, 30), new THREE.ShadowMaterial({ opacity: 0.12 }));
    ground.rotation.x = -Math.PI / 2;
    ground.receiveShadow = true;
    scene.add(ground);

    function render() { renderer.render(scene, camera); }
    function resetView() {
        camera.position.set(4.5, 4.1, 6.5);
        controls.target.set(0, 1.15, 0);
        controls.update();
        render();
    }
    function mesh(geometry, material, x, y, z) {
        const item = new THREE.Mesh(geometry, material);
        item.position.set(x, y, z);
        item.castShadow = true;
        item.receiveShadow = true;
        cake.add(item);
        return item;
    }
    function clearCake() {
        const materials = new Set();
        const geometries = new Set();
        cake.traverse((item) => {
            if (item.geometry) geometries.add(item.geometry);
            if (item.isInstancedMesh) item.dispose();
            if (item.material) materials.add(item.material);
        });
        geometries.forEach((geometry) => geometry.dispose());
        materials.forEach((material) => { material.map?.dispose(); material.dispose(); });
        cake.clear();
    }
    function rebuild() {
        clearCake();
        const size = form.elements.size.value;
        const occasion = form.elements.occasion.value;
        const shape = form.elements.shape.value;
        const rectangular = shape !== 'Round';
        const stretch = shape === 'Rectangle' ? 1.35 : 1;
        const color = form.querySelector('[name="frosting_color"]:checked');
        const tierMatch = size.match(/(\d+)\s*-?\s*tier/i);
        const tiers = tierMatch ? Math.min(3, Math.max(1, Number(tierMatch[1]))) : 1;
        const inchMatch = size.match(/(\d+)\s*(?:["”]|in\b|inch)/i) || (!tierMatch && size.match(/^(\d+)/));
        const radius = inchMatch ? Math.min(1.65, Math.max(1, Number(inchMatch[1]) / 6)) : 1.3;
        const frosting = new THREE.MeshStandardMaterial({ color: color?.dataset.hex || '#fbe3c4', roughness: 0.72 });
        const cream = new THREE.MeshStandardMaterial({ color: new THREE.Color(color?.dataset.hex || '#fbe3c4').lerp(new THREE.Color('white'), 0.35), roughness: 0.8 });
        const gold = new THREE.MeshStandardMaterial({ color: '#d9aa57', metalness: 0.55, roughness: 0.3 });
        mesh(rectangular ? new THREE.BoxGeometry((radius + 0.25) * 2 * stretch, 0.1, (radius + 0.25) * 2) : new THREE.CylinderGeometry(radius + 0.3, radius + 0.3, 0.1, 80), gold, 0, 0.08, 0);
        const height = tiers === 1 ? 1.15 : 0.78;
        for (let tier = 0; tier < tiers; tier++) {
            const r = radius * (1 - tier * 0.24);
            const bottom = 0.13 + tier * height;
            mesh(rectangular ? new THREE.BoxGeometry(r * 2 * stretch, height, r * 2) : new THREE.CylinderGeometry(r, r, height, 80), frosting, 0, bottom + height / 2, 0);
            // Piped buttercream borders around the top and foot of each tier.
            const count = Math.round(r * (rectangular ? 60 * stretch : 42));
            for (let i = 0; i < count; i++) {
                const angle = i / count * Math.PI * 2;
                const edge = rectangular ? 1 / Math.max(Math.abs(Math.cos(angle)), Math.abs(Math.sin(angle))) : 1;
                for (const y of [bottom + 0.05, bottom + height]) {
                    const dollop = mesh(new THREE.SphereGeometry(0.085, 10, 8), cream, Math.cos(angle) * (r - 0.045) * edge * stretch, y, Math.sin(angle) * (r - 0.045) * edge);
                    dollop.scale.set(1, 0.75, 1);
                }
            }
        }
        const top = 0.13 + tiers * height;
        const decoration = addFlavorToppings(cake, form.elements.flavor.value, radius * (1 - (tiers - 1) * 0.24), top, shape);
        const topper = addOccasionTopper(cake, occasion, radius * (1 - (tiers - 1) * 0.24), top);
        const theme = addThemeTopper(cake, form.elements.theme.value, radius * (1 - (tiers - 1) * 0.24), top);
        if (theme) {
            const occasionGroup = cake.children.find((child) => child.name.startsWith('occasion-'));
            if (occasionGroup) {
                occasionGroup.scale.multiplyScalar(0.48);
                occasionGroup.position.z = -0.32 * Math.min(1, radius * (1 - (tiers - 1) * 0.24) / 0.95);
            }
        }
        const details = [decoration, topper, theme].filter(Boolean).join(' and ');
        document.getElementById('cake-decoration-note').textContent = details
            ? `Shown with ${details}. Decorations are illustrative; describe your preferences below.`
            : 'Choose a flavor and occasion to preview matching decorations.';
        canvas.setAttribute('aria-description', `${shape}, ${size || 'single tier'} cake, ${color?.value || 'cream'} frosting${decoration ? `, ${decoration}` : ''}${occasion ? `, ${occasion} topper` : ''}${theme ? `, ${theme}` : ''}.`);
        render();
    }
    controls.addEventListener('change', render);
    form.addEventListener('cake:reset', resetView);
    canvas.addEventListener('keydown', (event) => {
        if (!['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', '+', '=', '-', 'Home'].includes(event.key)) return;
        event.preventDefault();
        if (event.key === 'Home') return resetView();
        const offset = camera.position.clone().sub(controls.target);
        const spherical = new THREE.Spherical().setFromVector3(offset);
        if (event.key === 'ArrowLeft') spherical.theta -= 0.15;
        if (event.key === 'ArrowRight') spherical.theta += 0.15;
        if (event.key === 'ArrowUp') spherical.phi -= 0.1;
        if (event.key === 'ArrowDown') spherical.phi += 0.1;
        if (['+', '='].includes(event.key)) spherical.radius *= 0.9;
        if (event.key === '-') spherical.radius *= 1.1;
        camera.position.copy(controls.target).add(new THREE.Vector3().setFromSpherical(spherical));
        controls.update();
    });
    const resize = new ResizeObserver(() => {
        if (!host.clientWidth || !host.clientHeight) return;
        renderer.setSize(host.clientWidth, host.clientHeight, false);
        camera.aspect = host.clientWidth / host.clientHeight;
        camera.updateProjectionMatrix();
        render();
    });
    form.addEventListener('change', (event) => {
        if (['size', 'shape', 'theme', 'occasion', 'flavor', 'frosting_color'].includes(event.target.name)) rebuild();
    });
    canvas.addEventListener('webglcontextlost', (event) => {
        event.preventDefault();
        canvas.hidden = true;
        canvas.style.display = 'none';
        fallback.style.display = '';
        status.textContent = '3D is unavailable. Your selections still update the simple preview.';
    });
    resetView();
    rebuild();
    host.append(canvas);
    fallback.style.display = 'none';
    status.textContent = 'Drag to rotate · Pinch to zoom · Arrow keys to rotate, + / − to zoom';
    resize.observe(host);
    window.addEventListener('pagehide', (event) => {
        if (event.persisted) return;
        resize.disconnect(); controls.dispose(); clearCake();
        ground.geometry.dispose(); ground.material.dispose(); renderer.dispose();
    }, { once: true });
}
