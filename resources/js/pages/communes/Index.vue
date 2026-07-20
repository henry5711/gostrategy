<script setup lang="ts">
import 'bootstrap/dist/css/bootstrap.min.css';
import { Head } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { computed, onMounted, reactive, ref } from 'vue';
import api from '@/lib/api';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Comunas', href: '/communes' }],
    },
});

type RegionOption = { id: number; name: string };
type Commune = {
    id: number;
    name: string;
    description: string | null;
    status: 'A' | 'I';
    region_id: number;
    region_name: string | null;
};

const communes = ref<Commune[]>([]);
const regions = ref<RegionOption[]>([]);
const loading = ref(false);
const search = ref('');
const regionFilter = ref('');
const alert = reactive<{ type: 'success' | 'danger' | null; message: string }>({
    type: null,
    message: '',
});

const filteredCommunes = computed(() => {
    return communes.value.filter((c) => {
        const matchesSearch = !search.value || c.name.toLowerCase().includes(search.value.toLowerCase());
        const matchesRegion = !regionFilter.value || String(c.region_id) === regionFilter.value;
        return matchesSearch && matchesRegion;
    });
});

function mapCommune(item: any): Commune {
    return {
        id: item.id,
        name: item.attributes.name,
        description: item.attributes.description,
        status: item.attributes.status,
        region_id: item.attributes.region_id,
        region_name: item.relationships?.region?.name ?? null,
    };
}

function showAlert(type: 'success' | 'danger', message: string) {
    alert.type = type;
    alert.message = message;
    setTimeout(() => {
        alert.type = null;
    }, 4000);
}

async function fetchRegions() {
    const { data } = await api.get('/regions');
    regions.value = data.data.map((item: any) => ({ id: item.id, name: item.attributes.name }));
}

async function fetchCommunes() {
    loading.value = true;
    try {
        const { data } = await api.get('/communes');
        communes.value = data.data.map(mapCommune);
    } catch (e) {
        showAlert('danger', 'No se pudieron cargar las comunas.');
    } finally {
        loading.value = false;
    }
}

// --- Create modal ---
const showCreateModal = ref(false);
const createForm = reactive({ region_id: '', name: '', description: '' });
const createErrors = ref<Record<string, string[]>>({});
const creating = ref(false);

function openCreateModal() {
    createForm.region_id = '';
    createForm.name = '';
    createForm.description = '';
    createErrors.value = {};
    showCreateModal.value = true;
}

async function submitCreate() {
    creating.value = true;
    createErrors.value = {};
    try {
        await api.post('/communes', createForm);
        showCreateModal.value = false;
        showAlert('success', 'Comuna creada correctamente.');
        await fetchCommunes();
    } catch (e: any) {
        if (e.response?.status === 422) {
            createErrors.value = e.response.data.errors;
        } else {
            showAlert('danger', 'Error interno al registrar la comuna.');
        }
    } finally {
        creating.value = false;
    }
}

// --- Edit modal ---
const showEditModal = ref(false);
const editForm = reactive({ id: 0, region_id: '', name: '', description: '', status: 'A' });
const editErrors = ref<Record<string, string[]>>({});
const editing = ref(false);

function openEditModal(commune: Commune) {
    editForm.id = commune.id;
    editForm.region_id = String(commune.region_id);
    editForm.name = commune.name;
    editForm.description = commune.description ?? '';
    editForm.status = commune.status;
    editErrors.value = {};
    showEditModal.value = true;
}

async function submitEdit() {
    editing.value = true;
    editErrors.value = {};
    try {
        await api.post(`/communes/${editForm.id}/update`, {
            region_id: editForm.region_id,
            name: editForm.name,
            description: editForm.description,
            status: editForm.status,
        });
        showEditModal.value = false;
        showAlert('success', 'Comuna actualizada correctamente.');
        await fetchCommunes();
    } catch (e: any) {
        if (e.response?.status === 422) {
            editErrors.value = e.response.data.errors;
        } else {
            showAlert('danger', 'Error interno al editar la comuna.');
        }
    } finally {
        editing.value = false;
    }
}

// --- Delete ---
async function deleteCommune(commune: Commune) {
    const result = await Swal.fire({
        title: `¿Eliminar la comuna "${commune.name}"?`,
        text: 'Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc3545',
    });

    if (!result.isConfirmed) return;

    try {
        await api.delete(`/communes/${commune.id}`);
        showAlert('success', 'Comuna eliminada correctamente.');
        await fetchCommunes();
    } catch (e: any) {
        showAlert('danger', e.response?.data?.message ?? 'Error al eliminar la comuna.');
    }
}

onMounted(async () => {
    await fetchRegions();
    await fetchCommunes();
});
</script>

<template>
    <Head title="Comunas" />

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
                <h5 class="mb-0">Comunas</h5>
                <button class="btn btn-primary btn-sm" @click="openCreateModal">
                    + Nueva comuna
                </button>
            </div>
            <div class="card-body">
                <div class="row mb-3 g-2">
                    <div class="col-md-6">
                        <input
                            v-model="search"
                            type="text"
                            class="form-control"
                            placeholder="Buscar por nombre..."
                        />
                    </div>
                    <div class="col-md-4">
                        <select v-model="regionFilter" class="form-select">
                            <option value="">Todas las regiones</option>
                            <option v-for="region in regions" :key="region.id" :value="String(region.id)">
                                {{ region.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Nombre</th>
                                <th>Región</th>
                                <th>Descripción</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="loading">
                                <td colspan="5" class="text-center text-muted">Cargando...</td>
                            </tr>
                            <tr v-else-if="filteredCommunes.length === 0">
                                <td colspan="5" class="text-center text-muted">
                                    No hay comunas registradas.
                                </td>
                            </tr>
                            <tr v-for="commune in filteredCommunes" :key="commune.id">
                                <td>{{ commune.name }}</td>
                                <td>{{ commune.region_name ?? '—' }}</td>
                                <td>{{ commune.description ?? '—' }}</td>
                                <td>
                                    <span
                                        :class="`badge ${commune.status === 'A' ? 'bg-success' : 'bg-secondary'}`"
                                    >
                                        {{ commune.status === 'A' ? 'Activo' : 'Desactivo' }}
                                    </span>
                                </td>
                                <td>
                                    <button
                                        class="btn btn-outline-secondary btn-sm me-2"
                                        @click="openEditModal(commune)"
                                    >
                                        Editar
                                    </button>
                                    <button
                                        class="btn btn-outline-danger btn-sm"
                                        @click="deleteCommune(commune)"
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
                            <h5 class="modal-title">Nueva comuna</h5>
                            <button
                                type="button"
                                class="btn-close"
                                @click="showCreateModal = false"
                            />
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Región</label>
                                <select
                                    v-model="createForm.region_id"
                                    class="form-select"
                                    :class="{ 'is-invalid': createErrors.region_id }"
                                    required
                                >
                                    <option value="" disabled>Selecciona una región</option>
                                    <option v-for="region in regions" :key="region.id" :value="String(region.id)">
                                        {{ region.name }}
                                    </option>
                                </select>
                                <div v-if="createErrors.region_id" class="invalid-feedback">
                                    {{ createErrors.region_id[0] }}
                                </div>
                            </div>
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
                            <h5 class="modal-title">Editar comuna</h5>
                            <button
                                type="button"
                                class="btn-close"
                                @click="showEditModal = false"
                            />
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Región</label>
                                <select
                                    v-model="editForm.region_id"
                                    class="form-select"
                                    :class="{ 'is-invalid': editErrors.region_id }"
                                    required
                                >
                                    <option v-for="region in regions" :key="region.id" :value="String(region.id)">
                                        {{ region.name }}
                                    </option>
                                </select>
                                <div v-if="editErrors.region_id" class="invalid-feedback">
                                    {{ editErrors.region_id[0] }}
                                </div>
                            </div>
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
