<script setup>
import { ref, computed, watch, onUnmounted } from 'vue';
import { route } from 'ziggy-js';
import axios from 'axios';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Loader, Smartphone, CheckCircle, XCircle } from 'lucide-vue-next';

const props = defineProps({
    venta: {
        type: Object,
        required: false,
        default: null
    },
    show: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['close', 'success']);

// Estado del componente
const loading = ref(false);
const verificando = ref(false);
const qrGenerado = ref(null);
const numeroTransaccion = ref(null);
const pagoId = ref(null);
const error = ref(null);
const success = ref(null);
const pagoConfirmado = ref(false);

// Formulario para generar QR
const formData = ref({
    venta_id: null,
    monto: 0,
    cliente_name: '',
    document_type: 1,
    document_id: '',
    phone_number: '',
    email: '',
});

// Watch para actualizar el formulario cuando cambie la venta
watch(() => props.venta, (newVenta) => {
    if (newVenta) {
        formData.value = {
            venta_id: newVenta.codigo_venta,
            monto: parseFloat(newVenta.saldo_pendiente) || 0,
            cliente_name: newVenta.cliente?.name || newVenta.cliente || '',
            document_type: 1,
            document_id: newVenta.cliente?.nit || '',
            phone_number: newVenta.cliente?.telefono || '',
            email: newVenta.cliente?.email || '',
        };
    }
}, { immediate: true });

// Computed
const saldoPendiente = computed(() => {
    return parseFloat(props.venta?.saldo_pendiente || 0);
});

const totalVenta = computed(() => {
    return parseFloat(props.venta?.total || 0);
});

const formatCurrency = (value) => {
    const number = parseFloat(value) || 0;
    return number.toFixed(2);
};

// Métodos
const generarQR = async () => {
    if (loading.value) return;

    loading.value = true;
    error.value = null;
    success.value = null;

    try {
        // ✅ Usando Ziggy para generar la ruta
        const response = await axios.post(route('pagos.qr.generar'), formData.value, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });

        if (response.data.success) {
            qrGenerado.value = response.data.data.qrImage;
            numeroTransaccion.value = response.data.data.transactionId;
            pagoId.value = response.data.data.pago_id;
            success.value = '¡QR generado exitosamente!';

            // Iniciar verificación automática
            iniciarVerificacionAutomatica();
        }
    } catch (err) {
        console.error('Error completo:', err);

        // Manejo de errores mejorado
        if (err.response?.status === 422) {
            // Errores de validación
            const errors = err.response.data.errors;
            if (errors) {
                error.value = Object.values(errors).flat().join(', ');
            } else {
                error.value = err.response.data.message || 'Error de validación';
            }
        } else if (err.response?.status === 401) {
            error.value = 'No estás autenticado. Por favor, inicia sesión nuevamente.';
        } else if (err.response?.status === 404) {
            error.value = 'No se encontró el endpoint. Verifica que las rutas estén configuradas correctamente.';
        } else {
            error.value = err.response?.data?.message || 'Error al generar el QR';
        }
    } finally {
        loading.value = false;
    }
};

const verificarPago = async () => {
    if (!numeroTransaccion.value || verificando.value) return;

    verificando.value = true;
    error.value = null;

    try {
        // ✅ Usando Ziggy para verificar pago
        const response = await axios.post(route('pagos.qr.verificar'), {
            transactionId: numeroTransaccion.value
        });

        if (response.data.success) {
            const estado = response.data.estado;

            if (estado === 'completado') {
                await confirmarPago();
            } else if (estado === 'rechazado' || estado === 'cancelado') {
                detenerVerificacion();
                error.value = `Pago ${estado}. Por favor, intente nuevamente.`;
            } else {
                // Estado pendiente, continuar verificando
                console.log('Estado del pago:', estado);
            }
        }
    } catch (err) {
        console.error('Error al verificar:', err);
        // No mostramos error al usuario para no interrumpir la verificación automática
    } finally {
        verificando.value = false;
    }
};

const confirmarPago = async () => {
    if (!pagoId.value) return;

    try {
        // ✅ Usando Ziggy para confirmar pago
        const response = await axios.post(route('pagos.qr.confirmar'), {
            pago_id: pagoId.value,
            transactionId: numeroTransaccion.value
        });

        if (response.data.success) {
            pagoConfirmado.value = true;
            success.value = '¡Pago confirmado exitosamente!';
            detenerVerificacion();

            // Cerrar modal y recargar después de 2 segundos
            setTimeout(() => {
                emit('success');
                cerrarModal();
            }, 2000);
        }
    } catch (err) {
        error.value = err.response?.data?.message || 'Error al confirmar el pago';
        console.error('Error al confirmar:', err);
    }
};

let intervaloVerificacion = null;

const iniciarVerificacionAutomatica = () => {
    let intentos = 0;
    const maxIntentos = 60; // 5 minutos (60 * 5 segundos)

    intervaloVerificacion = setInterval(async () => {
        intentos++;

        if (intentos >= maxIntentos || pagoConfirmado.value) {
            detenerVerificacion();
            if (intentos >= maxIntentos && !pagoConfirmado.value) {
                error.value = 'Tiempo de espera agotado. Por favor, verifica el pago manualmente.';
            }
            return;
        }

        await verificarPago();
    }, 5000); // Cada 5 segundos
};

const detenerVerificacion = () => {
    if (intervaloVerificacion) {
        clearInterval(intervaloVerificacion);
        intervaloVerificacion = null;
    }
};

const cerrarModal = () => {
    detenerVerificacion();
    resetearFormulario();
    emit('close');
};

const resetearFormulario = () => {
    qrGenerado.value = null;
    numeroTransaccion.value = null;
    pagoId.value = null;
    error.value = null;
    success.value = null;
    pagoConfirmado.value = false;
    loading.value = false;
    verificando.value = false;
};

const pagarTodo = () => {
    formData.value.monto = saldoPendiente.value;
};

// Limpiar al desmontar
onUnmounted(() => {
    detenerVerificacion();
});
</script>

<template>
    <Modal :show="show" @close="cerrarModal" max-width="2xl">
        <template #header>
            <h2 class="text-xl font-bold text-gray-900 flex items-center">
                <Smartphone :size="24" class="mr-2 text-blue-600" />
                Pago con QR - Venta #{{ venta?.numero_venta }}
            </h2>
        </template>

        <!-- Body -->
        <div class="p-6">
            <!-- Resumen de la Venta -->
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-4 mb-6">
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <p class="text-xs text-gray-600 mb-1">Cliente</p>
                        <p class="text-sm font-semibold text-gray-900">
                            {{ venta?.cliente?.name || venta?.cliente }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-600 mb-1">Total Venta</p>
                        <p class="text-sm font-semibold text-gray-900">
                            Bs {{ formatCurrency(totalVenta) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-600 mb-1">Saldo Pendiente</p>
                        <p class="text-lg font-bold text-red-600">
                            Bs {{ formatCurrency(saldoPendiente) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Formulario o QR -->
            <div v-if="!qrGenerado">
                <form @submit.prevent="generarQR" class="space-y-4">
                    <!-- Monto -->
                    <div>
                        <InputLabel for="monto">Monto a Pagar (Bs) *</InputLabel>
                        <div class="flex gap-2">
                            <TextInput
                                id="monto"
                                v-model.number="formData.monto"
                                type="number"
                                step="0.01"
                                min="0.1"
                                :max="saldoPendiente"
                                required
                                placeholder="0.00"
                                class="flex-1"
                            />
                            <SecondaryButton type="button" @click="pagarTodo">
                                Pagar Todo
                            </SecondaryButton>
                        </div>
                    </div>

                    <!-- Datos del Cliente -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="cliente_name">Nombre Completo *</InputLabel>
                            <TextInput
                                id="cliente_name"
                                v-model="formData.cliente_name"
                                required
                                placeholder="Juan Pérez"
                            />
                        </div>

                        <div>
                            <InputLabel for="email">Email *</InputLabel>
                            <TextInput
                                id="email"
                                v-model="formData.email"
                                type="email"
                                required
                                placeholder="cliente@ejemplo.com"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <InputLabel for="document_type">Tipo Documento *</InputLabel>
                            <select
                                id="document_type"
                                v-model.number="formData.document_type"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                                <option value="1">CI</option>
                                <option value="2">NIT</option>
                                <option value="3">Pasaporte</option>
                                <option value="4">Otro</option>
                                <option value="5">Sin documento</option>
                            </select>
                        </div>

                        <div>
                            <InputLabel for="document_id">N° Documento *</InputLabel>
                            <TextInput
                                id="document_id"
                                v-model="formData.document_id"
                                required
                                placeholder="1234567"
                            />
                        </div>

                        <div>
                            <InputLabel for="phone_number">Teléfono *</InputLabel>
                            <TextInput
                                id="phone_number"
                                v-model="formData.phone_number"
                                type="tel"
                                required
                                placeholder="75540850"
                            />
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4">
                        <SecondaryButton type="button" @click="cerrarModal">
                            Cancelar
                        </SecondaryButton>
                        <PrimaryButton type="submit" :disabled="loading" class="bg-blue-600 hover:bg-blue-700">
                            <Loader v-if="loading" :size="16" class="mr-2 animate-spin" />
                            <Smartphone v-else :size="16" class="mr-2" />
                            {{ loading ? 'Generando...' : 'Generar QR' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>

            <!-- QR Generado -->
            <div v-else class="text-center">
                <div v-if="!pagoConfirmado">
                    <h3 class="text-lg font-semibold mb-4">Escanea el QR para pagar</h3>
                    <div class="flex justify-center mb-4">
                        <img
                            :src="qrGenerado"
                            alt="Código QR"
                            class="max-w-xs border-4 border-blue-500 rounded-lg shadow-lg"
                        >
                    </div>
                    <p class="text-2xl font-bold text-green-600 mb-4">
                        Bs {{ formatCurrency(formData.monto) }}
                    </p>

                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                        <div v-if="verificando" class="flex items-center justify-center gap-2">
                            <Loader :size="20" class="animate-spin text-blue-600" />
                            <p class="text-sm text-gray-700">Verificando pago...</p>
                        </div>
                        <p v-else class="text-sm text-gray-700">
                            Esperando confirmación del pago...
                        </p>
                    </div>

                    <div class="flex justify-center gap-3">
                        <SecondaryButton @click="verificarPago" :disabled="verificando">
                            <Loader v-if="verificando" :size="16" class="mr-2 animate-spin" />
                            Verificar Ahora
                        </SecondaryButton>
                        <SecondaryButton @click="cerrarModal">
                            Cancelar
                        </SecondaryButton>
                    </div>
                </div>

                <!-- Pago Confirmado -->
                <div v-else class="py-8">
                    <CheckCircle :size="64" class="mx-auto text-green-500 mb-4" />
                    <h3 class="text-2xl font-bold text-green-600 mb-2">¡Pago Confirmado!</h3>
                    <p class="text-gray-600">El pago se ha procesado exitosamente</p>
                </div>
            </div>

            <!-- Mensajes -->
            <div v-if="error" class="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg flex items-start gap-2">
                <XCircle :size="20" class="text-red-600 flex-shrink-0 mt-0.5" />
                <p class="text-sm text-red-800">{{ error }}</p>
            </div>
            <div v-if="success && !pagoConfirmado" class="mt-4 p-4 bg-green-50 border border-green-200 rounded-lg flex items-start gap-2">
                <CheckCircle :size="20" class="text-green-600 flex-shrink-0 mt-0.5" />
                <p class="text-sm text-green-800">{{ success }}</p>
            </div>
        </div>
    </Modal>
</template>

<style scoped>
@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

.animate-spin {
    animation: spin 1s linear infinite;
}
</style>
