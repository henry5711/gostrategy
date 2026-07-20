<script setup lang="ts">
import 'bootstrap/dist/css/bootstrap.min.css';
import { Head } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { computed, onMounted, reactive, ref } from 'vue';
import api from '@/lib/api';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Usuarios', href: '/users' }],
    },
});

type RegionOption = { id: number; name: string };
type CommuneOption = { id: number; name: string; region_id: number };
type UserRow = {
    id: number;
    dni: string | null;
    name: string;
    last_name: string | null;
    email: string;
    address: string | null;
    region_id: number | null;
    commune_id: number | null;
    region_name: string | null;
    commune_name: string | null;
};

const users = ref<UserRow[]>([]);
const regions = ref<RegionOption[]>([]);
const communes = ref<CommuneOption[]>([]);
const loading = ref(false);
const search = ref('');
const regionFilter = ref('');
const alert = reactive<{ type: 'success' | 'danger' | null; message: string }>({
    type: null,
    message: '',
});

const filteredUsers = computed(() => {
    return users.value.filter((u) => {
        const term = search.value.toLowerCase();
        const matchesSearch =
            !search.value ||
            u.name.toLowerCase().includes(term) ||
            (u.last_name ?? '').toLowerCase().includes(term) ||
            u.email.toLowerCase().includes(term) ||
            (u.dni ?? '').toLowerCase().includes(term);
        const matchesRegion = !regionFilter.value || String(u.region_id) === regionFilter.value;
        return matchesSearch && matchesRegion;
    });
});

function mapUser(item: any): UserRow {
    return {
        id: item.id,
        dni: item.attributes.dni ?? null,
        name: item.attributes.name,
        last_name: item.attributes.last_name,
        email: item.attributes.email,
        address: item.attributes.address,
        region_id: item.relationships?.region?.id ?? null,
        commune_id: item.relationships?.commune?.id ?? null,
        region_name: item.relationships?.region?.name ?? null,
        commune_name: item.relationships?.commune?.name ?? null,
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
    const { data } = await api.get('/communes');
    communes.value = data.data.map((item: any) => ({
        id: item.id,
        name: item.attributes.name,
        region_id: item.attributes.region_id,
    }));
}

async function fetchUsers() {
    loading.value = true;
    try {
        const { data } = await api.get('/users');
        users.value = data.data.map(mapUser);
    } catch (e) {
        showAlert('danger', 'No se pudieron cargar los usuarios.');
    } finally {
        loading.value = false;
    }
}

function communesForRegion(regionId: string | number | null) {
    if (!regionId) return [];
    return communes.value.filter((c) => String(c.region_id) === String(regionId));
}

// --- Create modal ---
const showCreateModal = ref(false);
const createForm = reactive({
    dni: '',
    name: '',
    last_name: '',
    email: '',
    password: '',
    address: '',
    region_id: '',
    commune_id: '',
});
const createErrors = ref<Record<string, string[]>>({});
const creating = ref(false);
const createCommuneOptions = computed(() => communesForRegion(createForm.region_id));

function openCreateModal() {
    Object.assign(createForm, {
        dni: '',
        name: '',
        last_name: '',
        email: '',
        password: '',
        address: '',
        region_id: '',
        commune_id: '',
    });
    createErrors.value = {};
    showCreateModal.value = true;
}

async function submitCreate() {
    creating.value = true;
    createErrors.value = {};
    try {
        await api.post('/users', createForm);
        showCreateModal.value = false;
        showAlert('success', 'Usuario creado correctamente.');
        await fetchUsers();
    } catch (e: any) {
        if (e.response?.status === 422) {
            createErrors.value = e.response.data.errors;
        } else {
            showAlert('danger', 'Error interno al registrar el usuario.');
        }
    } finally {
        creating.value = false;
    }
}

// --- Edit modal ---
const showEditModal = ref(false);
const editForm = reactive({
    id: 0,
    name: '',
    last_name: '',
    email: '',
    address: '',
    region_id: '',
    commune_id: '',
});
const editErrors = ref<Record<string, string[]>>({});
const editing = ref(false);
const editCommuneOptions = computed(() => communesForRegion(editForm.region_id));

function openEditModal(user: UserRow) {
    editForm.id = user.id;
    editForm.name = user.name;
    editForm.last_name = user.last_name ?? '';
    editForm.email = user.email;
    editForm.address = user.address ?? '';
    editForm.region_id = user.region_id ? String(user.region_id) : '';
    editForm.commune_id = user.commune_id ? String(user.commune_id) : '';
    editErrors.value = {};
    showEditModal.value = true;
}

async function submitEdit() {
    editing.value = true;
    editErrors.value = {};
    try {
        await api.post(`/users/${editForm.id}/update`, {
            name: editForm.name,
            last_name: editForm.last_name,
            email: editForm.email,
            address: editForm.address,
            region_id: editForm.region_id,
            commune_id: editForm.commune_id,
        });
        showEditModal.value = false;
        showAlert('success', 'Usuario actualizado correctamente.');
        await fetchUsers();
    } catch (e: any) {
        if (e.response?.status === 422) {
            editErrors.value = e.response.data.errors;
        } else {
            showAlert('danger', 'Error interno al editar el usuario.');
        }
    } finally {
        editing.value = false;
    }
}

// --- Delete ---
async function deleteUser(user: UserRow) {
    const result = await Swal.fire({
        title: `¿Eliminar a "${user.name} ${user.last_name ?? ''}"?`,
        text: 'El usuario se eliminará (borrado lógico) y no podrá iniciar sesión.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc3545',
    });

    if (!result.isConfirmed) return;

    try {
        await api.delete(`/users/${user.id}`);
        showAlert('success', 'Usuario eliminado correctamente.');
        await fetchUsers();
    } catch (e: any) {
        showAlert('danger', e.response?.data?.message ?? 'Error al eliminar el usuario.');
    }
}

onMounted(async () => {
    await Promise.all([fetchRegions(), fetchCommunes()]);
    await fetchUsers();
});
</script>

<template>
    <Head title="Usuarios" />

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
                <h5 class="mb-0">Usuarios</h5>
                <button class="btn btn-primary btn-sm" @click="openCreateModal">
                    + Nuevo usuario
                </button>
            </div>
            <div class="card-body">
                <div class="row mb-3 g-2">
                    <div class="col-md-6">
                        <input
                            v-model="search"
                            type="text"
                            class="form-control"
                            placeholder="Buscar por nombre, email o DNI..."
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
                                <th>Email</th>
                                <th>Región / Comuna</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="loading">
                                <td colspan="4" class="text-center text-muted">Cargando...</td>
                            </tr>
                            <tr v-else-if="filteredUsers.length === 0">
                                <td colspan="4" class="text-center text-muted">
                                    No hay usuarios registrados.
                                </td>
                            </tr>
                            <tr v-for="user in filteredUsers" :key="user.id">
                                <td>{{ user.name }} {{ user.last_name }}</td>
                                <td>{{ user.email }}</td>
                                <td>{{ user.region_name ?? '—' }} / {{ user.commune_name ?? '—' }}</td>
                                <td>
                                    <button
                                        class="btn btn-outline-secondary btn-sm me-2"
                                        @click="openEditModal(user)"
                                    >
                                        Editar
                                    </button>
                                    <button
                                        class="btn btn-outline-danger btn-sm"
                                        @click="deleteUser(user)"
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
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form @submit.prevent="submitCreate">
                        <div class="modal-header">
                            <h5 class="modal-title">Nuevo usuario</h5>
                            <button
                                type="button"
                                class="btn-close"
                                @click="showCreateModal = false"
                            />
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
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
                                <div class="col-md-6">
                                    <label class="form-label">Apellido</label>
                                    <input
                                        v-model="createForm.last_name"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': createErrors.last_name }"
                                        required
                                    />
                                    <div v-if="createErrors.last_name" class="invalid-feedback">
                                        {{ createErrors.last_name[0] }}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">DNI</label>
                                    <input
                                        v-model="createForm.dni"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': createErrors.dni }"
                                        required
                                    />
                                    <div v-if="createErrors.dni" class="invalid-feedback">
                                        {{ createErrors.dni[0] }}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input
                                        v-model="createForm.email"
                                        type="email"
                                        class="form-control"
                                        :class="{ 'is-invalid': createErrors.email }"
                                        required
                                    />
                                    <div v-if="createErrors.email" class="invalid-feedback">
                                        {{ createErrors.email[0] }}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Contraseña</label>
                                    <input
                                        v-model="createForm.password"
                                        type="password"
                                        class="form-control"
                                        :class="{ 'is-invalid': createErrors.password }"
                                        autocomplete="new-password"
                                        required
                                    />
                                    <div v-if="createErrors.password" class="invalid-feedback">
                                        {{ createErrors.password[0] }}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Dirección</label>
                                    <input
                                        v-model="createForm.address"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': createErrors.address }"
                                    />
                                    <div v-if="createErrors.address" class="invalid-feedback">
                                        {{ createErrors.address[0] }}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Región</label>
                                    <select
                                        v-model="createForm.region_id"
                                        class="form-select"
                                        :class="{ 'is-invalid': createErrors.region_id }"
                                        required
                                        @change="createForm.commune_id = ''"
                                    >
                                        <option value="" disabled>Selecciona una región</option>
                                        <option
                                            v-for="region in regions"
                                            :key="region.id"
                                            :value="String(region.id)"
                                        >
                                            {{ region.name }}
                                        </option>
                                    </select>
                                    <div v-if="createErrors.region_id" class="invalid-feedback">
                                        {{ createErrors.region_id[0] }}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Comuna</label>
                                    <select
                                        v-model="createForm.commune_id"
                                        class="form-select"
                                        :class="{ 'is-invalid': createErrors.commune_id }"
                                        :disabled="!createForm.region_id"
                                        required
                                    >
                                        <option value="" disabled>Selecciona una comuna</option>
                                        <option
                                            v-for="commune in createCommuneOptions"
                                            :key="commune.id"
                                            :value="String(commune.id)"
                                        >
                                            {{ commune.name }}
                                        </option>
                                    </select>
                                    <div v-if="createErrors.commune_id" class="invalid-feedback">
                                        {{ createErrors.commune_id[0] }}
                                    </div>
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
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form @submit.prevent="submitEdit">
                        <div class="modal-header">
                            <h5 class="modal-title">Editar usuario</h5>
                            <button
                                type="button"
                                class="btn-close"
                                @click="showEditModal = false"
                            />
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
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
                                <div class="col-md-6">
                                    <label class="form-label">Apellido</label>
                                    <input
                                        v-model="editForm.last_name"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': editErrors.last_name }"
                                        required
                                    />
                                    <div v-if="editErrors.last_name" class="invalid-feedback">
                                        {{ editErrors.last_name[0] }}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input
                                        v-model="editForm.email"
                                        type="email"
                                        class="form-control"
                                        :class="{ 'is-invalid': editErrors.email }"
                                        required
                                    />
                                    <div v-if="editErrors.email" class="invalid-feedback">
                                        {{ editErrors.email[0] }}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Dirección</label>
                                    <input
                                        v-model="editForm.address"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': editErrors.address }"
                                    />
                                    <div v-if="editErrors.address" class="invalid-feedback">
                                        {{ editErrors.address[0] }}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Región</label>
                                    <select
                                        v-model="editForm.region_id"
                                        class="form-select"
                                        :class="{ 'is-invalid': editErrors.region_id }"
                                        required
                                        @change="editForm.commune_id = ''"
                                    >
                                        <option value="" disabled>Selecciona una región</option>
                                        <option
                                            v-for="region in regions"
                                            :key="region.id"
                                            :value="String(region.id)"
                                        >
                                            {{ region.name }}
                                        </option>
                                    </select>
                                    <div v-if="editErrors.region_id" class="invalid-feedback">
                                        {{ editErrors.region_id[0] }}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Comuna</label>
                                    <select
                                        v-model="editForm.commune_id"
                                        class="form-select"
                                        :class="{ 'is-invalid': editErrors.commune_id }"
                                        :disabled="!editForm.region_id"
                                        required
                                    >
                                        <option value="" disabled>Selecciona una comuna</option>
                                        <option
                                            v-for="commune in editCommuneOptions"
                                            :key="commune.id"
                                            :value="String(commune.id)"
                                        >
                                            {{ commune.name }}
                                        </option>
                                    </select>
                                    <div v-if="editErrors.commune_id" class="invalid-feedback">
                                        {{ editErrors.commune_id[0] }}
                                    </div>
                                </div>
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
