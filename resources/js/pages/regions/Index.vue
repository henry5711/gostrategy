<script setup lang="ts">
import 'bootstrap/dist/css/bootstrap.min.css';
import { Head } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { computed, onMounted, reactive, ref } from 'vue';
import api from '@/lib/api';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Regiones', href: '/regions' }],
    },
});

type Region = {
    id: number;
    name: string;
    description: string | null;
    status: 'A' | 'I';
};

const regions = ref<Region[]>([]);
const loading = ref(false);
const search = ref('');
const alert = reactive<{ type: 'success' | 'danger' | null; message: string }>({
    type: null,
    message: '',
});

const filteredRegions = computed(() => {
    if (!search.value) return regions.value;
    const term = search.value.toLowerCase();
    return regions.value.filter((r) => r.name.toLowerCase().includes(term));
});

function mapRegion(item: any): Region {
    return {
        id: item.id,
        name: item.attributes.name,
        description: item.attributes.description,
        status: item.attributes.status,
    };
}

async function fetchRegions() {
    loading.value = true;
    try {
        const { data } = await api.get('/regions');
        regions.value = data.data.map(mapRegion);
    } catch (e) {
        showAlert('danger', 'No se pudieron cargar las regiones.');
    } finally {
        loading.value = false;
    }
}

function showAlert(type: 'success' | 'danger', message: string) {
    alert.type = type;
    alert.message = message;
    setTimeout(() => {
        alert.type = null;
    }, 4000);
}

// --- Create modal ---
const showCreateModal = ref(false);
const createForm = reactive({ name: '', description: '' });
const createErrors = ref<Record<string, string[]>>({});
const creating = ref(false);

function openCreateModal() {
    createForm.name = '';
    createForm.description = '';
    createErrors.value = {};
    showCreateModal.value = true;
}

async function submitCreate() {
    creating.value = true;
    createErrors.value = {};
    try {
        await api.post('/regions', createForm);
        showCreateModal.value = false;
        showAlert('success', 'Región creada correctamente.');
        await fetchRegions();
    } catch (e: any) {
        if (e.response?.status === 422) {
            createErrors.value = e.response.data.errors;
        } else {
            showAlert('danger', 'Error interno al registrar la región.');
        }
    } finally {
        creating.value = false;
    }
}

// --- Edit modal ---
const showEditModal = ref(false);
const editForm = reactive({ id: 0, name: '', description: '', status: 'A' });
const editErrors = ref<Record<string, string[]>>({});
const editing = ref(false);

function openEditModal(region: Region) {
    editForm.id = region.id;
    editForm.name = region.name;
    editForm.description = region.description ?? '';
    editForm.status = region.status;
    editErrors.value = {};
    showEditModal.value = true;
}

async function submitEdit() {
    editing.value = true;
    editErrors.value = {};
    try {
        await api.post(`/regions/${editForm.id}/update`, {
            name: editForm.name,
            description: editForm.description,
            status: editForm.status,
        });
        showEditModal.value = false;
        showAlert('success', 'Región actualizada correctamente.');
        await fetchRegions();
    } catch (e: any) {
        if (e.response?.status === 422) {
            editErrors.value = e.response.data.errors;
        } else {
            showAlert('danger', 'Error interno al editar la región.');
        }
    } finally {
        editing.value = false;
    }
}

// --- Delete ---
async function deleteRegion(region: Region) {
    const result = await Swal.fire({
        title: `¿Eliminar la región "${region.name}"?`,
        text: 'Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc3545',
    });

    if (!result.isConfirmed) return;

    try {
        await api.delete(`/regions/${region.id}`);
        showAlert('success', 'Región eliminada correctamente.');
        await fetchRegions();
    } catch (e: any) {
        showAlert('danger', e.response?.data?.message ?? 'Error al eliminar la región.');
    }
}

onMounted(fetchRegions);
</script>

<template>
    <Head title="Regiones" />

    <div class="container-fluid py-4">
        <div
            v-if="alert.type"
            :class="`alert alert-${alert.type} alert-dismissible`"
            role="alert"
        >
            {{ alert.message }}
        </div>

        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Regiones</h5>
                <button class="btn btn-primary btn-sm" @click="openCreateModal">
                    + Nueva región
                </button>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <input
                        v-model="search"
                        type="text"
                        class="form-control"
                        placeholder="Buscar por nombre..."
                    />
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="loading">
                                <td colspan="4" class="text-center text-muted">Cargando...</td>
                            </tr>
                            <tr v-else-if="filteredRegions.length === 0">
                                <td colspan="4" class="text-center text-muted">
                                    No hay regiones registradas.
                                </td>
                            </tr>
                            <tr v-for="region in filteredRegions" :key="region.id">
                                <td>{{ region.name }}</td>
                                <td>{{ region.description ?? '—' }}</td>
                                <td>
                                    <span
                                        :class="`badge ${region.status === 'A' ? 'bg-success' : 'bg-secondary'}`"
                                    >
                                        {{ region.status === 'A' ? 'Activo' : 'Desactivo' }}
                                    </span>
                                </td>
                                <td>
                                    <button
                                        class="btn btn-outline-secondary btn-sm me-2"
                                        @click="openEditModal(region)"
                                    >
                                        Editar
                                    </button>
                                    <button
                                        class="btn btn-outline-danger btn-sm"
                                        @click="deleteRegion(region)"
                                    >
                                        Eliminar
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Create modal -->
        <div
            v-if="showCreateModal"
            class="modal d-block"
            tabindex="-1"
            style="background: rgba(0, 0, 0, 0.5)"
        >
            <div class="modal-dialog">
                <div class="modal-content">
                    <form @submit.prevent="submitCreate">
                        <div class="modal-header">
                            <h5 class="modal-title">Nueva región</h5>
                            <button
                                type="button"
                                class="btn-close"
                                @click="showCreateModal = false"
                            />
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Nombre</label>
                                <input
                                    v-model="createForm.name"
                                    type="text"
                                    class="form-control"
                                    :class="{ 'is-invalid': createErrors.name }"
                                    required
                                />
                                <div v-if="createErrors.name" class="invalid-feedback">
                                    {{ createErrors.name[0] }}
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Descripción</label>
                                <textarea
                                    v-model="createForm.description"
                                    class="form-control"
                                    rows="3"
                                    :class="{ 'is-invalid': createErrors.description }"
                                />
                                <div v-if="createErrors.description" class="invalid-feedback">
                                    {{ createErrors.description[0] }}
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button
                                type="button"
                                class="btn btn-secondary"
                                @click="showCreateModal = false"
                            >
                                Cancelar
                            </button>
                            <button type="submit" class="btn btn-primary" :disabled="creating">
                                Guardar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit modal -->
        <div
            v-if="showEditModal"
            class="modal d-block"
            tabindex="-1"
            style="background: rgba(0, 0, 0, 0.5)"
        >
            <div class="modal-dialog">
                <div class="modal-content">
                    <form @submit.prevent="submitEdit">
                        <div class="modal-header">
                            <h5 class="modal-title">Editar región</h5>
                            <button
                                type="button"
                                class="btn-close"
                                @click="showEditModal = false"
                            />
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Nombre</label>
                                <input
                                    v-model="editForm.name"
                                    type="text"
                                    class="form-control"
                                    :class="{ 'is-invalid': editErrors.name }"
                                    required
                                />
                                <div v-if="editErrors.name" class="invalid-feedback">
                                    {{ editErrors.name[0] }}
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Descripción</label>
                                <textarea
                                    v-model="editForm.description"
                                    class="form-control"
                                    rows="3"
                                    :class="{ 'is-invalid': editErrors.description }"
                                />
                                <div v-if="editErrors.description" class="invalid-feedback">
                                    {{ editErrors.description[0] }}
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Estado</label>
                                <select v-model="editForm.status" class="form-select">
                                    <option value="A">Activo</option>
                                    <option value="I">Desactivo</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button
                                type="button"
                                class="btn btn-secondary"
                                @click="showEditModal = false"
                            >
                                Cancelar
                            </button>
                            <button type="submit" class="btn btn-primary" :disabled="editing">
                                Guardar cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
