<template>
  <Modal :show="show" @close="closeModal" max-width="2xl">
    <template #header>
      <h3 class="text-lg font-semibold text-gray-900">
        {{ title }}
      </h3>
    </template>

    <div class="p-6">
      <!-- Paso 1: Seleccionar método de pago -->
      <div v-if="step === 1">
        <div class="mb-4">
          <InputLabel>Selecciona el método de pago</InputLabel>
          <p class="text-sm text-gray-500 mt-1">
            Elige tu banco o billetera digital preferida
          </p>
        </div>

        <!-- Loading métodos -->
        <div v-if="loadingMethods" class="flex justify-center items-center py-8">
          <Loader class="animate-spin text-blue-600" :size="32" />
          <span class="ml-3 text-gray-600">Cargando métodos de pago...</span>
        </div>

        <!-- Lista de métodos -->
        <div v-else-if="paymentMethods.length > 0" class="space-y-3 max-h-96 overflow-y-auto">
          <div
            v-for="method in paymentMethods"
            :key="method.paymentMethodId"
            @click="selectPaymentMethod(method)"
            class="border-2 rounded-lg p-4 cursor-pointer transition-all hover:border-blue-500 hover:bg-blue-50"
            :class="selectedMethod?.paymentMethodId === method.paymentMethodId ? 'border-blue-500 bg-blue-50' : 'border-gray-200'"
          >
            <div class="flex justify-between items-start">
              <div>
                <h4 class="font-semibold text-gray-900">{{ method.paymentMethodName }}</h4>
                <p class="text-sm text-gray-500 mt-1">Moneda: {{ method.currencyName }}</p>
              </div>
              <div class="text-right text-sm text-gray-600">
                <p>Máx por transacción:</p>
                <p class="font-semibold">Bs. {{ formatMoney(method.maxAmountPerTransaction) }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Error cargando métodos -->
        <div v-else class="text-center py-8">
          <p class="text-red-600">{{ errorMessage || 'No hay métodos de pago disponibles' }}</p>
        </div>

        <!-- Monto personalizado (para créditos) -->
        <div v-if="allowPartialPayment && selectedMethod" class="mt-6">
          <InputLabel for="custom_amount">Monto a pagar (opcional)</InputLabel>
          <TextInput
            id="custom_amount"
            v-model.number="customAmount"
            type="number"
            step="0.01"
            min="0.1"
            :max="maxAmount"
            placeholder="Dejar vacío para pagar el total"
            class="mt-1"
          />
          <p class="text-sm text-gray-500 mt-1">
            Monto total: Bs. {{ formatMoney(totalAmount) }}
            <span v-if="customAmount">(Pagando: Bs. {{ formatMoney(customAmount) }})</span>
          </p>
        </div>

        <!-- Botones -->
        <div class="flex justify-end space-x-3 mt-6">
          <SecondaryButton @click="closeModal">
            Cancelar
          </SecondaryButton>
          <PrimaryButton
            @click="generateQR"
            :disabled="!selectedMethod || generating"
          >
            <Loader v-if="generating" class="animate-spin mr-2" :size="16" />
            Generar QR
          </PrimaryButton>
        </div>
      </div>

      <!-- Paso 2: Mostrar QR y esperar pago -->
      <div v-if="step === 2" class="text-center">
        <!-- QR Code -->
        <div class="mb-6 flex justify-center">
          <div class="bg-white p-4 rounded-lg shadow-lg border-2 border-blue-500">
            <img
              v-if="qrData.qr_base64"
              :src="`data:image/png;base64,${qrData.qr_base64}`"
              alt="Código QR"
              class="w-64 h-64"
            />
            <div v-else class="w-64 h-64 flex items-center justify-center bg-gray-100">
              <Loader class="animate-spin text-blue-600" :size="48" />
            </div>
          </div>
        </div>

        <!-- Información -->
        <div class="mb-6">
          <h4 class="text-xl font-bold text-gray-900 mb-2">
            Escanea el código QR
          </h4>
          <p class="text-gray-600 mb-4">
            Usa tu aplicación bancaria para escanear el código y completar el pago
          </p>

          <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
            <p class="text-sm font-semibold text-blue-900">
              Monto a pagar: <span class="text-2xl">Bs. {{ formatMoney(qrData.amount) }}</span>
            </p>
            <p class="text-xs text-blue-700 mt-1">
              ID Transacción: {{ qrData.company_transaction_id }}
            </p>
          </div>

          <!-- Temporizador -->
          <div v-if="qrData.expiration_date" class="text-sm text-gray-500">
            <Clock :size="16" class="inline mr-1" />
            Este QR expira el: {{ formatDate(qrData.expiration_date) }}
          </div>
        </div>

        <!-- Botones alternativos -->
        <div v-if="qrData.deep_link || qrData.universal_url" class="space-y-2 mb-6">
          <a
            v-if="qrData.deep_link"
            :href="qrData.deep_link"
            target="_blank"
            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
          >
            <Smartphone :size="18" class="mr-2" />
            Abrir en App
          </a>

          <a
            v-if="qrData.universal_url"
            :href="qrData.universal_url"
            target="_blank"
            class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors ml-2"
          >
            <ExternalLink :size="18" class="mr-2" />
            Pagar en Navegador
          </a>
        </div>

        <!-- Estado del pago -->
        <div class="mb-6">
          <div v-if="checkingStatus" class="flex items-center justify-center text-gray-600">
            <Loader class="animate-spin mr-2" :size="20" />
            Verificando estado del pago...
          </div>

          <div v-else-if="paymentStatus === 'completed'" class="text-green-600 flex items-center justify-center">
            <CheckCircle :size="24" class="mr-2" />
            <span class="font-semibold">¡Pago completado exitosamente!</span>
          </div>

          <div v-else-if="paymentStatus === 'failed'" class="text-red-600 flex items-center justify-center">
            <XCircle :size="24" class="mr-2" />
            <span class="font-semibold">El pago falló. Por favor intenta nuevamente.</span>
          </div>

          <div v-else class="text-gray-600">
            <p class="text-sm">Esperando confirmación del pago...</p>
            <p class="text-xs mt-1">El estado se actualizará automáticamente</p>
          </div>
        </div>

        <!-- Botones -->
        <div class="flex justify-center space-x-3">
          <SecondaryButton
            v-if="paymentStatus !== 'completed'"
            @click="checkStatus"
            :disabled="checkingStatus"
          >
            <RefreshCw :size="16" class="mr-2" :class="{ 'animate-spin': checkingStatus }" />
            Verificar Estado
          </SecondaryButton>

          <PrimaryButton
            v-if="paymentStatus === 'completed'"
            @click="closeAndRefresh"
          >
            Finalizar
          </PrimaryButton>

          <SecondaryButton
            v-else
            @click="closeModal"
          >
            Cerrar
          </SecondaryButton>
        </div>
      </div>
    </div>
  </Modal>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import {
  Loader,
  CheckCircle,
  XCircle,
  Clock,
  Smartphone,
  ExternalLink,
  RefreshCw
} from 'lucide-vue-next';

const props = defineProps({
  show: Boolean,
  title: {
    type: String,
    default: 'Realizar Pago con QR'
  },
  ventaId: [Number, String],
  cuotaId: [Number, String],
  totalAmount: {
    type: Number,
    required: true
  },
  allowPartialPayment: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['close', 'success']);

// Estado
const step = ref(1); // 1: Seleccionar método, 2: Mostrar QR
const paymentMethods = ref([]);
const selectedMethod = ref(null);
const customAmount = ref(null);
const loadingMethods = ref(false);
const generating = ref(false);
const errorMessage = ref('');
const qrData = ref({});
const transactionId = ref(null);
const checkingStatus = ref(false);
const paymentStatus = ref('pending');
let statusCheckInterval = null;

// Computed
const maxAmount = computed(() => {
  if (!selectedMethod.value) return props.totalAmount;
  return Math.min(props.totalAmount, selectedMethod.value.maxAmountPerTransaction);
});

// Funciones
const loadPaymentMethods = async () => {
  loadingMethods.value = true;
  errorMessage.value = '';

  try {
    const response = await axios.get('/api/payment/methods');

    if (response.data.success) {
      paymentMethods.value = response.data.methods;
    } else {
      errorMessage.value = response.data.message || 'Error al cargar métodos de pago';
    }
  } catch (error) {
    console.error('Error loading payment methods:', error);
    errorMessage.value = error.response?.data?.message || 'Error al cargar métodos de pago';
  } finally {
    loadingMethods.value = false;
  }
};

const selectPaymentMethod = (method) => {
  selectedMethod.value = method;
};

const generateQR = async () => {
  generating.value = true;
  errorMessage.value = '';

  try {
    const endpoint = props.cuotaId
      ? `/api/cuotas/${props.cuotaId}/generate-payment-qr`
      : `/api/ventas/${props.ventaId}/generate-payment-qr`;

    const payload = {
      payment_method_id: selectedMethod.value.paymentMethodId,
    };

    if (customAmount.value && customAmount.value > 0) {
      payload.monto = customAmount.value;
    }

    const response = await axios.post(endpoint, payload);

    if (response.data.success) {
      qrData.value = response.data.transaction;
      transactionId.value = response.data.transaction.id;
      step.value = 2;
      startStatusChecking();
    } else {
      errorMessage.value = response.data.message || 'Error al generar código QR';
    }
  } catch (error) {
    console.error('Error generating QR:', error);
    errorMessage.value = error.response?.data?.message || 'Error al generar código QR';
  } finally {
    generating.value = false;
  }
};

const checkStatus = async () => {
  if (!transactionId.value) return;

  checkingStatus.value = true;

  try {
    const response = await axios.get(`/api/transactions/${transactionId.value}/status`);

    if (response.data.success) {
      const transaction = response.data.transaction;
      paymentStatus.value = transaction.status;

      if (transaction.status === 'completed') {
        stopStatusChecking();
        setTimeout(() => {
          emit('success', transaction);
        }, 2000);
      }
    }
  } catch (error) {
    console.error('Error checking status:', error);
  } finally {
    checkingStatus.value = false;
  }
};

const startStatusChecking = () => {
  // Verificar cada 5 segundos
  statusCheckInterval = setInterval(() => {
    checkStatus();
  }, 5000);

  // Primera verificación inmediata
  setTimeout(() => {
    checkStatus();
  }, 1000);
};

const stopStatusChecking = () => {
  if (statusCheckInterval) {
    clearInterval(statusCheckInterval);
    statusCheckInterval = null;
  }
};

const closeModal = () => {
  stopStatusChecking();
  step.value = 1;
  selectedMethod.value = null;
  customAmount.value = null;
  qrData.value = {};
  transactionId.value = null;
  paymentStatus.value = 'pending';
  errorMessage.value = '';
  emit('close');
};

const closeAndRefresh = () => {
  closeModal();
  // Recargar la página para actualizar los datos
  router.reload();
};

const formatMoney = (amount) => {
  return Number(amount).toLocaleString('es-BO', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
};

const formatDate = (date) => {
  return new Date(date).toLocaleString('es-BO', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

// Lifecycle
watch(() => props.show, (newValue) => {
  if (newValue) {
    loadPaymentMethods();
  }
});

onUnmounted(() => {
  stopStatusChecking();
});
</script>
