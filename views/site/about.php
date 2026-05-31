<?php

/** @var yii\web\View $this */

use yii\helpers\Html;

$this->title = 'Acerca de TOMAKO';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-about">
    <h1><?= Html::encode($this->title) ?></h1>

    <div class="about-content">
        <!-- MISIÓN -->
        <section class="mb-4">
            <h2>🎯 Nuestra Misión</h2>
            <p>
                En virtud de los procesos de transformación digital en la sociedad, 
                desarrollamos <strong>software libre ajustado a la realidad local</strong>, 
                aprovechando el uso de la inteligencia artificial para crear soluciones 
                tecnológicas accesibles. Nuestro compromiso es proporcionar herramientas 
                digitales de calidad que operen en hosting de bajo costo, diseñadas 
                específicamente para <strong>PyMEs</strong>, democratizando el acceso a 
                sistemas de gestión empresarial modernos y eficientes.
            </p>
        </section>

        <!-- VISIÓN -->
        <section class="mb-4">
            <h2>👁️ Nuestra Visión</h2>
            <p>
                Aspiramos a construir una comunidad de <strong>código abierto</strong> donde 
                desarrollar, compartir y aprender sean pilares fundamentales. Buscamos mejorar 
                el bienestar de la sociedad mediante la creación colaborativa de software que 
                empodere a pequeños y medianos empresarios, fomentando la innovación tecnológica 
                inclusiva y el crecimiento sostenible de los negocios locales.
            </p>
        </section>

        <!-- TOMAKO -->
        <section class="mb-4">
            <h2>🍅 ¿Qué es TOMAKO?</h2>
            <p>
                <strong>TOMAKO</strong> (<em>Taller Operativo de Mecánica Avanzada y Kinemática Optimizada</em>) 
                es un sistema integral de gestión para talleres mecánicos automotrices, desarrollado en 
                <strong>PHP con el framework Yii2</strong> y base de datos <strong>MySQL/MariaDB</strong>.
            </p>
            <p>
                <strong>Tu auto necesita TOMAKO</strong> 🍅 — porque representamos la fusión entre 
                <strong>energía orgánica y precisión industrial</strong>: un taller mecánico con carácter, 
                cercano pero experto, vibrante pero confiable.
            </p>
        </section>

        <!-- PROPÓSITO -->
        <section class="mb-4">
            <h2>🚀 Propósito del Proyecto</h2>
            <p>
                TOMAKO nace para <strong>digitalizar y optimizar el flujo de trabajo de talleres mecánicos</strong>, 
                centralizando todas las operaciones del taller en una plataforma moderna: desde la recepción 
                del cliente hasta la entrega del vehículo, pasando por diagnóstico, órdenes de servicio, 
                gestión de inventario y seguimiento técnico.
            </p>
            <p>
                Proporcionamos:
            </p>
            <ul>
                <li><strong>Trazabilidad completa</strong> de cada intervención</li>
                <li><strong>Control de inventario</strong> en tiempo real con alertas de stock bajo</li>
                <li><strong>Agendamiento inteligente</strong> de citas con validación de solapamientos</li>
                <li><strong>Dashboard con KPIs</strong> para toma de decisiones informadas</li>
                <li><strong>Auditoría detallada</strong> de todas las operaciones</li>
                <li><strong>Gestión de clientes y vehículos</strong> con validación de RUT y patente chilena</li>
                <li><strong>Módulo de pagos</strong> con múltiples métodos y estados</li>
                <li><strong>Sistema de notificaciones</strong> multi-canal (email, push, interna)</li>
                <li><strong>Gestión de técnicos</strong> con especialidades y certificaciones</li>
                <li><strong>Órdenes de compra y proveedores</strong> para gestión integral de insumos</li>
            </ul>
        </section>

        <!-- TECNOLOGÍA -->
        <section class="mb-4">
            <h2>🛠️ Stack Tecnológico</h2>
            <ul>
                <li><strong>Backend:</strong> PHP 8.1+ con Yii2 Framework</li>
                <li><strong>Base de datos:</strong> MySQL/MariaDB</li>
                <li><strong>Frontend:</strong> Bootstrap 5, JavaScript vanilla</li>
                <li><strong>Cache:</strong> Redis/File cache para KPIs</li>
                <li><strong>Email:</strong> SwiftMailer / Symfony Mailer</li>
            </ul>
            <p>
                Esta arquitectura está pensada para operar eficientemente en entornos de 
                <strong>hosting de bajo costo</strong>, haciendo que la tecnología de punta 
                sea accesible para todos los talleres mecánicos, sin importar su tamaño.
            </p>
        </section>

        <!-- CÓDIGO ABIERTO -->
        <section class="mb-4">
            <h2>🤝 Código Abierto para la Sociedad</h2>
            <p>
                Este proyecto se construye bajo la filosofía del <strong>software libre</strong>. 
                Creemos firmemente que la tecnología debe ser una herramienta de inclusión y 
                desarrollo social. Por eso, invitamos a la comunidad a:
            </p>
            <ul>
                <li><strong>Construir</strong> junto a nosotros nuevas funcionalidades</li>
                <li><strong>Compartir</strong> conocimiento y mejoras</li>
                <li><strong>Aprender</strong> de un código base documentado y estructurado</li>
            </ul>
            <p>
                Cada contribución nos acerca más a nuestro objetivo de mejorar el bienestar 
                de la sociedad mediante herramientas digitales que potencien el trabajo local.
            </p>
        </section>

        <!-- LICENCIA -->
        <section class="mb-4">
            <h2>📄 Licencia</h2>
            <p>
                TOMAKO está distribuido bajo la <strong>Licencia MIT</strong>, lo que significa 
                que eres libre de usarlo, modificarlo y distribuirlo, siempre que mantengas los 
                créditos originales.
            </p>
        </section>

        <div class="alert alert-info mt-4">
            <strong>💡 ¿Quieres contribuir?</strong> 
            Revisa nuestro repositorio, reporta issues, envía pull requests o simplemente 
            comparte tu experiencia usando TOMAKO. ¡Juntos hacemos crecer el ecosistema!
        </div>
    </div>

    <style>
        .site-about {
            max-width: 900px;
            margin: 0 auto;
        }
        .about-content section {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        .about-content h2 {
            color: #E63946;
            border-bottom: 2px solid #7A5230;
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
        }
        .about-content ul {
            padding-left: 1.5rem;
        }
        .about-content li {
            margin-bottom: 0.5rem;
        }
        .alert-info {
            background-color: #e7f3ff;
            border: 1px solid #b3d9ff;
            color: #004085;
            padding: 1rem;
            border-radius: 4px;
        }
    </style>
</div>
