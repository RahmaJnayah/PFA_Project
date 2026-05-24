(function() {
      // Get container element
      const container = document.getElementById('canvas-container');
      if (!container || typeof THREE === 'undefined') {
        console.error('Three.js not loaded or container not found');
        return;
      }

      // ─────────────────────────────────────────────────────────
      // 1. SCENE SETUP
      // ─────────────────────────────────────────────────────────
      const scene = new THREE.Scene();
      scene.background = new THREE.Color(0x040111); // Deep void black-purple
      
      // Camera with perspective
      const camera = new THREE.PerspectiveCamera(55, container.clientWidth / container.clientHeight, 0.1, 1000);
      camera.position.set(0, 0, 14);
      camera.lookAt(0, 0, 0);
      
      // WebGL Renderer
      const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: false });
      renderer.setSize(container.clientWidth, container.clientHeight);
      renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
      renderer.setClearColor(0x040111, 1);
      container.appendChild(renderer.domElement);
      
      // ─────────────────────────────────────────────────────────
      // 2. MAIN ORB GROUP (for rotation and animation)
      // ─────────────────────────────────────────────────────────
      const coreGroup = new THREE.Group();
      scene.add(coreGroup);
      
      // ─────────────────────────────────────────────────────────
      // 3. CENTRAL ORB (glowing sphere)
      // ─────────────────────────────────────────────────────────
      const orbGeometry = new THREE.SphereGeometry(2.5, 128, 128);
      const orbMaterial = new THREE.MeshStandardMaterial({
        color: 0x8b5cf6,
        emissive: 0x4c1d95,
        emissiveIntensity: 0.55,
        metalness: 0.7,
        roughness: 0.25,
        transparent: true,
        opacity: 0.95
      });
      const orb = new THREE.Mesh(orbGeometry, orbMaterial);
      coreGroup.add(orb);
      
      // Inner glow layer
      const innerGlowGeometry = new THREE.SphereGeometry(2.65, 64, 64);
      const innerGlowMaterial = new THREE.MeshBasicMaterial({
        color: 0xa78bfa,
        transparent: true,
        opacity: 0.08,
        side: THREE.BackSide
      });
      const innerGlow = new THREE.Mesh(innerGlowGeometry, innerGlowMaterial);
      coreGroup.add(innerGlow);
      
      // Outer aura sphere
      const outerGlowGeometry = new THREE.SphereGeometry(3.3, 48, 48);
      const outerGlowMaterial = new THREE.MeshBasicMaterial({
        color: 0x7c3aed,
        transparent: true,
        opacity: 0.1,
        side: THREE.BackSide
      });
      const outerGlow = new THREE.Mesh(outerGlowGeometry, outerGlowMaterial);
      coreGroup.add(outerGlow);
      
      // Wireframe decorative mesh
      const wireGeometry = new THREE.IcosahedronGeometry(3.8, 1);
      const wireMaterial = new THREE.MeshBasicMaterial({
        color: 0xc4b5fd,
        wireframe: true,
        transparent: true,
        opacity: 0.12
      });
      const wireframe = new THREE.Mesh(wireGeometry, wireMaterial);
      coreGroup.add(wireframe);
      
      // Second wireframe (smaller, rotated)
      const wireGeometry2 = new THREE.IcosahedronGeometry(3.2, 0);
      const wireMaterial2 = new THREE.MeshBasicMaterial({
        color: 0x69daff,
        wireframe: true,
        transparent: true,
        opacity: 0.08
      });
      const wireframe2 = new THREE.Mesh(wireGeometry2, wireMaterial2);
      coreGroup.add(wireframe2);
      
      // ─────────────────────────────────────────────────────────
      // 4. RINGS AROUND THE ORB
      // ─────────────────────────────────────────────────────────
      function createRing(radius, tube, rotX, rotY, rotZ, color, opacity) {
        const geometry = new THREE.TorusGeometry(radius, tube, 64, 200);
        const material = new THREE.MeshBasicMaterial({ color: color, transparent: true, opacity: opacity });
        const ring = new THREE.Mesh(geometry, material);
        ring.rotation.x = rotX;
        ring.rotation.y = rotY;
        ring.rotation.z = rotZ;
        coreGroup.add(ring);
        return ring;
      }
      
      // Create multiple rings with different colors and angles
      const ring1 = createRing(3.95, 0.025, Math.PI * 0.28, 0, Math.PI * 0.05, 0xa78bfa, 0.58);
      const ring2 = createRing(4.55, 0.018, Math.PI * 0.50, Math.PI * 0.2, Math.PI * 0.32, 0xec4899, 0.45);
      const ring3 = createRing(5.05, 0.012, Math.PI * 0.14, Math.PI * 0.35, Math.PI * 0.60, 0x22d3ee, 0.32);
      const ring4 = createRing(5.55, 0.009, Math.PI * 0.65, Math.PI * 0.15, Math.PI * 0.20, 0xffb703, 0.25);
      const ring5 = createRing(6.00, 0.006, Math.PI * 0.42, Math.PI * 0.5, Math.PI * 0.80, 0xffffff, 0.15);
      
      // ─────────────────────────────────────────────────────────
      // 5. PARTICLE SYSTEMS (Stars and sparkles)
      // ─────────────────────────────────────────────────────────
      
      // Helper: Create soft circular texture for particles
      function createParticleTexture() {
        const canvas = document.createElement('canvas');
        canvas.width = 32;
        canvas.height = 32;
        const ctx = canvas.getContext('2d');
        ctx.beginPath();
        ctx.arc(16, 16, 14, 0, 2 * Math.PI);
        ctx.fillStyle = 'white';
        ctx.fill();
        ctx.globalCompositeOperation = 'source-over';
        ctx.shadowBlur = 0;
        return new THREE.CanvasTexture(canvas);
      }
      
      const particleTexture = createParticleTexture();
      
      // Function to create a particle field
      function createParticleField(count, color, size, spreadX, spreadY, spreadZ, opacity, blending = THREE.AdditiveBlending) {
        const positions = new Float32Array(count * 3);
        for (let i = 0; i < count; i++) {
          positions[i * 3] = (Math.random() - 0.5) * spreadX;
          positions[i * 3 + 1] = (Math.random() - 0.5) * spreadY;
          positions[i * 3 + 2] = (Math.random() - 0.5) * spreadZ;
        }
        const geometry = new THREE.BufferGeometry();
        geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
        
        const material = new THREE.PointsMaterial({
          color: color,
          size: size,
          map: particleTexture,
          transparent: true,
          opacity: opacity,
          blending: blending,
          depthWrite: false
        });
        
        const points = new THREE.Points(geometry, material);
        scene.add(points);
        return points;
      }
      
      // Create multiple particle layers for depth
      const particlesPurple = createParticleField(2000, 0xa78bfa, 0.11, 32, 24, 28, 0.62);
      const particlesPink = createParticleField(1400, 0xec4899, 0.09, 28, 22, 24, 0.48);
      const particlesCyan = createParticleField(1800, 0x22d3ee, 0.08, 30, 26, 26, 0.44);
      const particlesGold = createParticleField(1000, 0xffb703, 0.07, 26, 20, 22, 0.38);
      const particlesWhite = createParticleField(2500, 0xffffff, 0.05, 40, 32, 35, 0.32);
      
      // Distant stars (smaller, more transparent)
      const distantStars = createParticleField(3000, 0xffffff, 0.03, 50, 40, 45, 0.25);
      
      // ─────────────────────────────────────────────────────────
      // 6. NEBULA EFFECT (using fog and colored lights)
      // ─────────────────────────────────────────────────────────
      
      // Add fog for depth
      scene.fog = new THREE.FogExp2(0x040111, 0.008);
      
      // Ambient light
      const ambientLight = new THREE.AmbientLight(0x1a1030, 0.45);
      scene.add(ambientLight);
      
      // Point lights for dynamic illumination
      const light1 = new THREE.PointLight(0xa78bfa, 0.9, 25);
      light1.position.set(3, 4, 5);
      scene.add(light1);
      
      const light2 = new THREE.PointLight(0xec4899, 0.7, 22);
      light2.position.set(-4, 2, 4);
      scene.add(light2);
      
      const light3 = new THREE.PointLight(0x22d3ee, 0.6, 20);
      light3.position.set(2, -3, 6);
      scene.add(light3);
      
      const light4 = new THREE.PointLight(0xffb703, 0.5, 18);
      light4.position.set(-2, 5, 3);
      scene.add(light4);
      
      // Back light for rim lighting
      const backLight = new THREE.PointLight(0xffffff, 0.4, 20);
      backLight.position.set(0, 0, -8);
      scene.add(backLight);
      
      // ─────────────────────────────────────────────────────────
      // 7. ANIMATION VARIABLES
      // ─────────────────────────────────────────────────────────
      let time = 0;
      let mouseX = 0;
      let mouseY = 0;
      
      // Target rotation for parallax effect
      let targetRotationX = 0;
      let targetRotationY = 0;
      let currentRotationX = 0;
      let currentRotationY = 0;
      
      // Track mouse position for parallax
      document.addEventListener('mousemove', (event) => {
        mouseX = (event.clientX / window.innerWidth) * 2 - 1;
        mouseY = (event.clientY / window.innerHeight) * 2 - 1;
        targetRotationY = mouseX * 0.5;
        targetRotationX = mouseY * 0.3;
      });
      
      // Touch support for mobile
      document.addEventListener('touchmove', (event) => {
        if (event.touches.length) {
          mouseX = (event.touches[0].clientX / window.innerWidth) * 2 - 1;
          mouseY = (event.touches[0].clientY / window.innerHeight) * 2 - 1;
          targetRotationY = mouseX * 0.5;
          targetRotationX = mouseY * 0.3;
        }
      }, { passive: true });
      
      // ─────────────────────────────────────────────────────────
      // 8. ANIMATION LOOP
      // ─────────────────────────────────────────────────────────
      function animate() {
        requestAnimationFrame(animate);
        time += 0.012;
        
        // Smooth rotation for core group (auto-rotation)
        coreGroup.rotation.y += 0.0025;
        coreGroup.rotation.x = Math.sin(time * 0.2) * 0.08;
        coreGroup.rotation.z = Math.cos(time * 0.15) * 0.04;
        
        // Individual ring rotations
        ring1.rotation.z += 0.005;
        ring1.rotation.x += 0.002;
        ring2.rotation.x += 0.0035;
        ring2.rotation.z -= 0.0015;
        ring3.rotation.y += 0.003;
        ring3.rotation.x -= 0.001;
        ring4.rotation.z -= 0.002;
        ring4.rotation.y += 0.0018;
        ring5.rotation.x += 0.001;
        ring5.rotation.z += 0.0012;
        
        // Wireframe rotations
        wireframe.rotation.x += 0.001;
        wireframe.rotation.y -= 0.002;
        wireframe2.rotation.x -= 0.0008;
        wireframe2.rotation.z += 0.0012;
        
        // Particle field rotations (slow drift)
        particlesPurple.rotation.y += 0.0003;
        particlesPurple.rotation.x += 0.00015;
        particlesPink.rotation.y -= 0.00025;
        particlesPink.rotation.z += 0.0002;
        particlesCyan.rotation.x += 0.0002;
        particlesCyan.rotation.z -= 0.00015;
        particlesGold.rotation.y += 0.0002;
        particlesWhite.rotation.x += 0.0001;
        particlesWhite.rotation.y -= 0.0001;
        distantStars.rotation.y += 0.00005;
        
        // Animate lights positions (slow movement)
        light1.position.x = 3 + Math.sin(time * 0.5) * 1.5;
        light1.position.y = 4 + Math.cos(time * 0.6) * 1.2;
        light2.position.x = -4 + Math.cos(time * 0.45) * 1.8;
        light2.position.z = 4 + Math.sin(time * 0.55) * 1.5;
        light3.position.y = -3 + Math.sin(time * 0.7) * 1.3;
        light3.position.x = 2 + Math.cos(time * 0.4) * 1.4;
        light4.position.x = -2 + Math.sin(time * 0.35) * 1.6;
        light4.position.y = 5 + Math.cos(time * 0.5) * 1.1;
        
        // Orb emissive intensity pulsing
        const intensity = 0.55 + Math.sin(time * 1.5) * 0.08;
        orbMaterial.emissiveIntensity = intensity;
        
        // Parallax effect - smooth camera follow
        currentRotationX += (targetRotationX - currentRotationX) * 0.05;
        currentRotationY += (targetRotationY - currentRotationY) * 0.05;
        
        camera.position.x += (currentRotationY * 1.2 - camera.position.x) * 0.04;
        camera.position.y += (-currentRotationX * 0.8 - camera.position.y) * 0.04;
        camera.lookAt(0, 0, 0);
        
        // Render scene
        renderer.render(scene, camera);
      }
      
      // Start animation
      animate();
      
      // ─────────────────────────────────────────────────────────
      // 9. HANDLE WINDOW RESIZE
      // ─────────────────────────────────────────────────────────
      window.addEventListener('resize', onWindowResize, false);
      
      function onWindowResize() {
        const width = container.clientWidth;
        const height = container.clientHeight;
        
        camera.aspect = width / height;
        camera.updateProjectionMatrix();
        renderer.setSize(width, height);
      }
      
      // Initial call to set proper size
      onWindowResize();
      
      // Small console log to confirm script is running
      console.log('Three.js cosmic background initialized with ' + 
                  (particlesPurple.geometry.attributes.position.count + 
                   particlesPink.geometry.attributes.position.count + 
                   particlesCyan.geometry.attributes.position.count + 
                   particlesGold.geometry.attributes.position.count + 
                   particlesWhite.geometry.attributes.position.count + 
                   distantStars.geometry.attributes.position.count) + ' particles');
    })();

