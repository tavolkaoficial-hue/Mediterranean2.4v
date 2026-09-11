<?php
// Configuración MAMP
$host = "localhost";
$user = "root";
$pass = "root";
$db   = "mediterranean";
$port = 8889;

$conn = @new mysqli($host, $user, $pass, $db, $port);

// Limpieza y lectura del token
$token = isset($_GET['token']) ? trim($_GET['token']) : (isset($_POST['token']) ? trim($_POST['token']) : '');
$error_db = $conn->connect_error;

$usuario_datos = [
    'nombre_completo' => '', 
    'correo' => '', 
    'telefono' => '', 
    'seguro_social' => '', 
    'direccion' => ''
];

if (!empty($token) && !$error_db) {
    $token_escapado = $conn->real_escape_string($token);
    $res_user = $conn->query("SELECT * FROM usuarios WHERE token_registro = '$token_escapado' LIMIT 1");
    
    if ($res_user && $res_user->num_rows > 0) {
        $usuario_datos = $res_user->fetch_assoc();
    }
}

$registro_exitoso = false;
$mensaje_status = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error_db) {
    $token_post      = $conn->real_escape_string($_POST['token'] ?? '');
    $seguro_social   = $conn->real_escape_string($_POST['seguro_social'] ?? '');
    $direccion       = $conn->real_escape_string($_POST['direccion'] ?? '');
    $foto_base64     = $conn->real_escape_string($_POST['foto_base64'] ?? '');

    if (!empty($token_post)) {
        $sql = "UPDATE usuarios SET 
                seguro_social = '$seguro_social', 
                direccion = '$direccion', 
                foto_biometrica = '$foto_base64', 
                estado = 'activo' 
                WHERE token_registro = '$token_post'";

        if ($conn->query($sql) === TRUE) {
            $registro_exitoso = true;
            // Refrescar datos actualizados para mostrarlos en la card de confirmación
            $res_user = $conn->query("SELECT * FROM usuarios WHERE token_registro = '$token_post' LIMIT 1");
            if ($res_user && $res_user->num_rows > 0) {
                $usuario_datos = $res_user->fetch_assoc();
            }
        } else {
            $mensaje_status = "Error en base de datos: " . $conn->error;
        }
    } else {
        $mensaje_status = "El token enviado no es válido o está vacío.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verificación Biométrica</title>

  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=DM+Sans:wght@400;500;600;700&family=Orbitron:wght@500;700;800&display=swap" rel="stylesheet">

  <!-- MediaPipe -->
  <script src="https://cdn.jsdelivr.net/npm/@mediapipe/camera_utils/camera_utils.js" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/@mediapipe/face_mesh/face_mesh.js" crossorigin="anonymous"></script>

  <style>
    :root {
      --navy-deep: #09131f;
      --navy-glass: rgba(15, 28, 46, 0.65);
      --navy-border: rgba(120, 160, 220, 0.35);
      --glass-card: rgba(22, 42, 70, 0.58);
      --glass-shadow: 0 12px 40px 0 rgba(7, 14, 26, 0.35);
      --glass-blur: blur(20px);
      
      --gold: #d4af37;
      --gold-light: #f3e5ab;
      --gold-glow: rgba(212, 175, 55, 0.4);
      --text-light: #f1f5f9;
      --text-muted: #94a3b8;
      
      --transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    }

    * { box-sizing: border-box; }

    body {
      margin: 0;
      min-height: 100vh;
      color: var(--text-light);
      font-family: 'DM Sans', sans-serif;
      background: linear-gradient(135deg, #09131f 0%, #172a45 50%, #0d1d33 100%);
      background-attachment: fixed;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1.5rem 1rem;
    }

    /* Ambient Dynamic Lights */
    body::before, body::after {
      content: "";
      position: fixed;
      border-radius: 50%;
      filter: blur(120px);
      z-index: -1;
      pointer-events: none;
      animation: floatGlow 10s ease-in-out infinite alternate;
    }
    body::before {
      width: 600px; height: 600px;
      top: -150px; left: -100px;
      background: rgba(30, 64, 110, 0.4);
    }
    body::after {
      width: 650px; height: 650px;
      bottom: -150px; right: -100px;
      background: rgba(212, 175, 55, 0.15);
    }

    @keyframes floatGlow {
      0% { transform: translate(0, 0) scale(1); }
      100% { transform: translate(30px, -20px) scale(1.08); }
    }

    /* Glassmorphism Card */
    .glass-card {
      position: relative;
      overflow: hidden;
      background: var(--glass-card);
      backdrop-filter: var(--glass-blur);
      -webkit-backdrop-filter: var(--glass-blur);
      border: 1px solid var(--navy-border);
      border-radius: 1.5rem;
      box-shadow: var(--glass-shadow);
      transition: var(--transition);
    }

    .font-garamond { font-family: 'Cormorant Garamond', serif; }
    .font-orbitron { font-family: 'Orbitron', sans-serif; }

    .brand-logo-img {
      width: 160px;
      height: 160px;
      object-fit: contain;
      filter: drop-shadow(0 8px 18px rgba(0,0,0,0.4)) drop-shadow(0 0 12px var(--gold-glow));
      animation: logoPulse 4s ease-in-out infinite alternate;
    }

    @keyframes logoPulse {
      0% { transform: scale(1); filter: drop-shadow(0 8px 18px rgba(0,0,0,0.4)) drop-shadow(0 0 10px rgba(212, 175, 55, 0.3)); }
      100% { transform: scale(1.04); filter: drop-shadow(0 12px 24px rgba(0,0,0,0.5)) drop-shadow(0 0 20px rgba(212, 175, 55, 0.6)); }
    }

    .btn-futuristic {
      background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
      color: #fff;
      font-weight: 600;
      box-shadow: 0 4px 15px rgba(2, 132, 199, 0.3);
      transition: var(--transition);
    }
    .btn-futuristic:hover:not(:disabled) {
      background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
      box-shadow: 0 6px 20px rgba(14, 165, 233, 0.4);
      transform: translateY(-2px);
    }

    /* Biometric Scan Elements */
    .faceid-container {
      position: relative;
      width: 210px;
      height: 210px;
      margin: 0 auto;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .faceid-view {
      width: 170px;
      height: 170px;
      border-radius: 50%;
      overflow: hidden;
      position: relative;
      background: #020617;
      box-shadow: 0 0 25px rgba(14, 165, 233, 0.3);
      border: 2px solid rgba(14, 165, 233, 0.6);
    }

    #webcam { width: 100%; height: 100%; object-fit: cover; transform: scaleX(-1); }
    #meshCanvas { position: absolute; top: 0; left: 0; width: 100%; height: 100%; transform: scaleX(-1); pointer-events: none; }

    .progress-ring { position: absolute; top: 0; left: 0; width: 210px; height: 210px; transform: rotate(-90deg); pointer-events: none; }
    .progress-ring__circle-bg { stroke: rgba(255, 255, 255, 0.08); stroke-width: 6; }
    .progress-ring__circle {
      stroke: #0ea5e9; stroke-width: 6; stroke-linecap: round;
      stroke-dasharray: 600; stroke-dashoffset: 600;
      transition: stroke-dashoffset 0.3s ease, stroke 0.3s ease;
      filter: drop-shadow(0 0 8px rgba(14, 165, 233, 0.6));
    }

    .scanner-laser {
      position: absolute; width: 100%; height: 2px;
      background: linear-gradient(90deg, transparent, #38bdf8, #10b981, transparent);
      top: 0; left: 0; box-shadow: 0 0 12px #38bdf8;
      animation: scanLaser 1.8s infinite ease-in-out; display: none;
    }
    @keyframes scanLaser { 0% { top: 0%; } 50% { top: 98%; } 100% { top: 0%; } }

    .step-dots { display: flex; justify-content: center; gap: 8px; }
    .dot { width: 8px; height: 8px; border-radius: 50%; background: rgba(255, 255, 255, 0.2); transition: all 0.3s ease; }
    .dot.active { background: #0ea5e9; box-shadow: 0 0 10px #0ea5e9; transform: scale(1.3); }
    .dot.completed { background: #10b981; box-shadow: 0 0 10px #10b981; }

    /* Scenario Caminata */
    .stage-container {
      position: relative;
      width: 100%;
      height: 180px;
      background: radial-gradient(circle at center, rgba(15, 28, 46, 0.9), rgba(9, 19, 31, 0.95));
      border-radius: 1rem;
      border: 1px solid rgba(212, 175, 55, 0.3);
      box-shadow: inset 0 0 20px rgba(0, 0, 0, 0.5);
      overflow: hidden;
      margin-bottom: 1.25rem;
      display: flex;
      align-items: flex-end;
    }

    .stage-grid {
      position: absolute; width: 100%; height: 100%;
      background-image: 
        linear-gradient(rgba(120, 160, 220, 0.1) 1px, transparent 1px),
        linear-gradient(90deg, rgba(120, 160, 220, 0.1) 1px, transparent 1px);
      background-size: 20px 20px;
    }

    .path-line {
      position: absolute; bottom: 30px; left: 5%; width: 90%; height: 2px;
      background: linear-gradient(90deg, transparent, #d4af37, #10b981);
      box-shadow: 0 0 8px rgba(212, 175, 55, 0.5);
    }

    .walker {
      position: absolute; bottom: 30px; left: 10px; font-size: 3rem; color: #fbbf24;
      filter: drop-shadow(0 0 10px rgba(245, 158, 11, 0.6));
      animation: walkToBuilding 4s ease-in-out forwards, walkingBounce 0.4s infinite alternate;
      z-index: 5;
    }

    .hq-target {
      position: absolute; bottom: 20px; right: 25px; text-align: center; z-index: 5;
    }

    .hq-target i {
      font-size: 3.8rem;
      background: linear-gradient(180deg, #ffffff, #d4af37);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      filter: drop-shadow(0 0 15px rgba(212, 175, 55, 0.6));
    }

    .hq-target span {
      display: block; font-size: 0.7rem; font-weight: 700; color: #d4af37;
      letter-spacing: 1.5px; margin-top: 3px;
    }

    .welcome-banner {
      position: absolute; top: 20px; width: 100%; text-align: center;
      font-size: 1.1rem; font-weight: 800; color: #10b981; letter-spacing: 2px;
      text-shadow: 0 0 15px rgba(16, 185, 129, 0.5);
      opacity: 0; transform: translateY(-10px);
      animation: showWelcome 0.8s ease-out 3.8s forwards; z-index: 6;
    }

    @keyframes walkToBuilding {
      0% { left: 15px; opacity: 1; }
      80% { left: calc(100% - 110px); opacity: 1; transform: scale(1); }
      100% { left: calc(100% - 85px); opacity: 0; transform: scale(0.6); }
    }

    @keyframes walkingBounce {
      0% { transform: translateY(0); }
      100% { transform: translateY(-6px); }
    }

    @keyframes showWelcome {
      to { opacity: 1; transform: translateY(0); }
    }

    /* Scrollbars Custom */
    ::-webkit-scrollbar { width: 6px; }
    ::-webkit-scrollbar-track { background: rgba(15, 28, 46, 0.5); }
    ::-webkit-scrollbar-thumb { background: rgba(120, 160, 220, 0.3); border-radius: 10px; }
  </style>
</head>
<body>

  <div class="glass-card w-full max-w-lg p-6 sm:p-8 space-y-5">
    
    <!-- Logo & Header Corporativo -->
    <div class="flex flex-col items-center justify-center text-center pb-4 border-b border-slate-700/60">
      <img src="images/LogoMediterranean1992.png" alt="Mediterranean Logo" class="brand-logo-img mb-2" onerror="this.onerror=null; this.src='https://via.placeholder.com/90/172a45/d4af37?text=M';">
      <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-cyan-500/10 text-cyan-400 border border-cyan-500/30 mb-1">Módulo Biométrico RRHH</span>
     
    </div>

    <!-- Mensaje Status Error -->
    <?php if (!empty($mensaje_status)): ?>
      <div class="p-3 bg-rose-500/10 border border-rose-500/30 rounded-xl text-rose-300 text-xs flex items-center gap-2">
        <i class="fas fa-circle-exclamation text-rose-400 text-sm"></i>
        <span><?php echo htmlspecialchars($mensaje_status); ?></span>
      </div>
    <?php endif; ?>

    <!-- CARD DE ÉXITO COMPLETADO -->
    <?php if ($registro_exitoso): ?>
      <div class="text-center space-y-4">
        
        <div class="stage-container">
          <div class="stage-grid"></div>
          <div class="welcome-banner"><i class="fas fa-door-open mr-1"></i> ¡BIENVENIDO A MEDITERRANEAN!</div>
          <div class="path-line"></div>
          <i class="fas fa-person-walking walker"></i>
          <div class="hq-target">
            <i class="fas fa-building-user"></i>
            
          </div>
        </div>

        <h2 class="text-lg font-bold text-emerald-400 font-garamond tracking-wide flex items-center justify-center gap-2">
          <i class="fas fa-circle-check"></i> REGISTRO BIOMÉTRICO EXITOSO
        </h2>

        <div class="bg-slate-950/60 border border-slate-700/60 rounded-xl p-4 text-left space-y-2 text-xs">
          <div class="flex justify-between border-b border-slate-800 pb-1.5">
            <span class="text-slate-400 font-semibold"><i class="fas fa-user text-cyan-400 mr-1.5"></i>Nombre:</span>
            <span class="text-slate-200 font-bold"><?php echo htmlspecialchars($usuario_datos['nombre_completo'] ?: 'No registrado'); ?></span>
          </div>
          <div class="flex justify-between border-b border-slate-800 pb-1.5">
            <span class="text-slate-400 font-semibold"><i class="fas fa-envelope text-cyan-400 mr-1.5"></i>Correo:</span>
            <span class="text-slate-200"><?php echo htmlspecialchars($usuario_datos['correo'] ?: 'No registrado'); ?></span>
          </div>
          <div class="flex justify-between border-b border-slate-800 pb-1.5">
            <span class="text-slate-400 font-semibold"><i class="fas fa-phone text-cyan-400 mr-1.5"></i>Teléfono:</span>
            <span class="text-slate-200"><?php echo htmlspecialchars($usuario_datos['telefono'] ?: 'No registrado'); ?></span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-400 font-semibold"><i class="fas fa-id-card text-cyan-400 mr-1.5"></i>Cédula / SS:</span>
            <span class="text-slate-200 font-medium"><?php echo htmlspecialchars($usuario_datos['seguro_social'] ?: 'No registrado'); ?></span>
          </div>
        </div>

        <div class="p-3 bg-emerald-500/10 border border-emerald-500/30 rounded-xl text-emerald-400 text-xs font-semibold flex items-center justify-center gap-2">
          <i class="fas fa-shield-halved"></i> Identidad y Rostro Encriptados Correctamente
        </div>
      </div>

    <!-- FORMULARIO DE VERIFICACIÓN -->
    <?php else: ?>

      <form action="completar_registro.php" method="POST" id="bioForm" class="space-y-4">
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
        <input type="hidden" name="foto_base64" id="foto_base64" required>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Empleado</label>
            <input type="text" value="<?php echo htmlspecialchars($usuario_datos['nombre_completo']); ?>" readonly class="w-full p-2.5 bg-slate-950/80 rounded-xl border border-slate-800 text-xs text-slate-300 font-semibold focus:outline-none cursor-not-allowed">
          </div>
          <div>
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Correo Electrónico</label>
            <input type="email" value="<?php echo htmlspecialchars($usuario_datos['correo']); ?>" readonly class="w-full p-2.5 bg-slate-950/80 rounded-xl border border-slate-800 text-xs text-slate-300 font-semibold focus:outline-none cursor-not-allowed">
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Teléfono</label>
            <input type="text" value="<?php echo htmlspecialchars($usuario_datos['telefono']); ?>" readonly class="w-full p-2.5 bg-slate-950/80 rounded-xl border border-slate-800 text-xs text-slate-300 font-semibold focus:outline-none cursor-not-allowed">
          </div>
          <div>
            <label class="block text-[10px] font-bold text-amber-400/90 uppercase tracking-wider mb-1">Seguro Social / Cédula *</label>
            <input type="text" name="seguro_social" value="<?php echo htmlspecialchars($usuario_datos['seguro_social']); ?>" required placeholder="Ej: 8-901-234" class="w-full p-2.5 bg-slate-950/90 rounded-xl border border-slate-700 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-amber-400 transition">
          </div>
        </div>

        <div>
          <label class="block text-[10px] font-bold text-amber-400/90 uppercase tracking-wider mb-1">Dirección Residencial *</label>
          <textarea name="direccion" rows="2" required placeholder="Ubicación residencia del empleado" class="w-full p-2.5 bg-slate-950/90 rounded-xl border border-slate-700 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-amber-400 transition"><?php echo htmlspecialchars($usuario_datos['direccion']); ?></textarea>
        </div>

        <!-- Escáner Facial Biométrico -->
        <div class="pt-2 border-t border-slate-700/60 text-center space-y-2">
          <label class="block text-[11px] font-bold text-cyan-400 uppercase tracking-wider">
            <i class="fas fa-expand mr-1"></i> Escaneo Biométrico Facial
          </label>

          <div class="faceid-container">
            <svg class="progress-ring">
              <circle class="progress-ring__circle-bg" r="95" cx="105" cy="105" fill="transparent"/>
              <circle class="progress-ring__circle" id="progressCircle" r="95" cx="105" cy="105" fill="transparent"/>
            </svg>

            <div class="faceid-view">
              <video id="webcam" autoplay playsinline></video>
              <canvas id="meshCanvas"></canvas>
              <div class="scanner-laser" id="laser"></div>
            </div>
          </div>

          <div class="text-xs font-bold text-cyan-300 min-h-[20px]" id="statusText">Iniciando cámara...</div>

          <div class="step-dots">
            <div class="dot active" id="dot0"></div>
            <div class="dot" id="dot1"></div>
            <div class="dot" id="dot2"></div>
          </div>
        </div>

        <button type="submit" id="btnSubmit" disabled class="w-full py-3 rounded-xl text-xs font-bold opacity-40 cursor-not-allowed bg-slate-800 text-slate-400 border border-slate-700 transition flex items-center justify-center gap-2 mt-4">
          <i class="fas fa-lock"></i> Pendiente de verificación facial
        </button>
      </form>

    <?php endif; ?>

    <!-- Pie de página -->
    <div class="pt-3 border-t border-slate-800 text-center text-[10px] text-slate-500">
      © 2026 Mediterranean Technologies • Módulo de Seguridad Facial
    </div>

  </div>

  <script>
    const videoElement = document.getElementById('webcam');
    const canvasElement = document.getElementById('meshCanvas');
    const canvasCtx = canvasElement ? canvasElement.getContext('2d') : null;
    const statusText = document.getElementById('statusText');
    const progressCircle = document.getElementById('progressCircle');
    const laser = document.getElementById('laser');
    const btnSubmit = document.getElementById('btnSubmit');
    const inputBase64 = document.getElementById('foto_base64');

    if (progressCircle) {
      const radius = progressCircle.r.baseVal.value;
      const circumference = 2 * Math.PI * radius;
      
      function setProgress(percent) {
        const offset = circumference - (percent / 100) * circumference;
        progressCircle.style.strokeDashoffset = offset;
      }

      const steps = [
        { text: "1. MIRE DE FRENTE AL CENTRO", check: (yaw) => Math.abs(yaw) < 0.07 },
        { text: "2. GIRE A LA IZQUIERDA", check: (yaw) => yaw > 0.11 },
        { text: "3. GIRE A LA DERECHA", check: (yaw) => yaw < -0.11 }
      ];

      let currentStep = 0;
      let isCompleted = false;

      function updateStepDots(step) {
        for (let i = 0; i < 3; i++) {
          const dot = document.getElementById(`dot${i}`);
          if (!dot) continue;
          if (i < step) {
            dot.className = "dot completed";
          } else if (i === step) {
            dot.className = "dot active";
          } else {
            dot.className = "dot";
          }
        }
      }

      function drawMesh(landmarks) {
        if (!canvasCtx) return;
        canvasCtx.clearRect(0, 0, canvasElement.width, canvasElement.height);
        canvasCtx.fillStyle = '#38bdf8';
        
        const keyPoints = [1, 33, 263, 61, 291, 199, 10, 152, 234, 454];
        keyPoints.forEach(idx => {
          const pt = landmarks[idx];
          const x = pt.x * canvasElement.width;
          const y = pt.y * canvasElement.height;
          canvasCtx.beginPath();
          canvasCtx.arc(x, y, 2, 0, 2 * Math.PI);
          canvasCtx.fill();
        });
      }

      function onResults(results) {
        if (isCompleted) return;

        if (canvasElement && videoElement.videoWidth) {
          canvasElement.width = videoElement.videoWidth;
          canvasElement.height = videoElement.videoHeight;
        }

        if (results.multiFaceLandmarks && results.multiFaceLandmarks.length > 0) {
          laser.style.display = 'block';
          const landmarks = results.multiFaceLandmarks[0];

          drawMesh(landmarks);

          const nose = landmarks[1];
          const leftCheek = landmarks[234];
          const rightCheek = landmarks[454];
          const yaw = (nose.x - (leftCheek.x + rightCheek.x) / 2);

          if (currentStep < steps.length) {
            statusText.innerText = steps[currentStep].text;

            if (steps[currentStep].check(yaw)) {
              currentStep++;
              updateStepDots(currentStep);
              const progress = (currentStep / steps.length) * 100;
              setProgress(progress);

              if (currentStep === steps.length) {
                isCompleted = true;
                laser.style.display = 'none';
                progressCircle.style.stroke = "#10b981";
                statusText.innerText = "¡RECONOCIMIENTO EXITOSO!";
                statusText.className = "text-xs font-bold text-emerald-400 min-h-[20px]";

                if (canvasCtx) canvasCtx.clearRect(0, 0, canvasElement.width, canvasElement.height);

                const canvas = document.createElement('canvas');
                canvas.width = videoElement.videoWidth || 480;
                canvas.height = videoElement.videoHeight || 480;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(videoElement, 0, 0, canvas.width, canvas.height);
                inputBase64.value = canvas.toDataURL('image/png');

                btnSubmit.disabled = false;
                btnSubmit.className = "w-full py-3 rounded-xl text-xs font-bold btn-futuristic flex items-center justify-center gap-2 mt-4 cursor-pointer";
                btnSubmit.innerHTML = `<i class="fas fa-shield-check"></i> GUARDAR Y COMPLETAR REGISTRO`;
              }
            }
          }
        } else {
          laser.style.display = 'none';
          if (canvasCtx) canvasCtx.clearRect(0, 0, canvasElement.width, canvasElement.height);
          statusText.innerText = "POSICIONE SU ROSTRO EN EL MARCO";
          statusText.className = "text-xs font-bold text-cyan-300 min-h-[20px]";
        }
      }

      const faceMesh = new FaceMesh({
        locateFile: (file) => `https://cdn.jsdelivr.net/npm/@mediapipe/face_mesh/${file}`
      });

      faceMesh.setOptions({
        maxNumFaces: 1,
        refineLandmarks: false,
        minDetectionConfidence: 0.5,
        minTrackingConfidence: 0.5
      });

      faceMesh.onResults(onResults);

      const camera = new Camera(videoElement, {
        onFrame: async () => {
          await faceMesh.send({ image: videoElement });
        },
        width: 480,
        height: 480
      });

      camera.start();
    }
  </script>
</body>
</html>