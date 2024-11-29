<script setup>
import { FilterMatchMode } from '@primevue/core/api';
import { useToast } from 'primevue/usetoast';
import { onMounted, ref, computed } from 'vue';
import api from '@/services/api'; 
import { format } from 'date-fns'; 

const toast = useToast();
const dt = ref();
const records = ref([]);
const recordDialog = ref(false);
const deleteRecordDialog = ref(false);
const deleteRecordsDialog = ref(false);
const record = ref({});
const selectedRecords = ref();
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }
});
const submitted = ref(false);
const isEditMode = ref(false); 

const transactionTypes = [
    { label: 'Revenue', value: 'Revenue' },
    { label: 'Expense', value: 'Expense' }
];

const categories = [
    { label: 'Tax', value: 'Tax' },
    { label: 'Permit Fee', value: 'Permit Fee' },
    { label: 'Salary', value: 'Salary' },
    { label: 'Community Service Fee', value: 'Community Service Fee' },
    { label: 'Special Contribution', value: 'Special Contribution' },
    { label: 'Other Income', value: 'Other Income' }
];

const fetchFinancialRecords = async () => {
    try {
        const response = await api.get('/financial-records'); 
        records.value = response.data;
    } catch (error) {
        console.error('Error fetching financial records:', error);
        toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to fetch financial records' });
    }
};

onMounted(() => {
    fetchFinancialRecords();
});

const formattedRecords = computed(() => {
    return records.value.map(record => ({
        ...record,
        date: format(new Date(record.date), 'yyyy-MM-dd')
    }));
});

function openNew() {
    record.value = {}; 
    submitted.value = false;
    isEditMode.value = false; 
    recordDialog.value = true;
}

function hideDialog() {
    recordDialog.value = false;
    submitted.value = false;
}

async function saveRecord() {
    submitted.value = true;
    if (record.value.date && record.value.amount) {
        try {
            const recordData = {
                ...record.value,
                date: format(new Date(record.value.date), 'yyyy-MM-dd'),
                type: record.value.type.value, // Ensure type is a string
                category: record.value.category.value, // Ensure category is a string
            };

            if (record.value.id) {
                // Update existing record
                await api.put(`/financial-records/${record.value.id}`, recordData);
                records.value[findIndexById(record.value.id)] = recordData;
                toast.add({ severity: 'success', summary: 'Successful', detail: 'Record Updated', life: 3000 });
            } else {
                // Create new record
                const response = await api.post('/financial-records', recordData);
                record.value.id = response.data.id;
                records.value.push(recordData);
                toast.add({ severity: 'success', summary: 'Successful', detail: 'Record Created', life: 3000 });
            }
            recordDialog.value = false;
            record.value = {};
        } catch (error) {
            console.error('Error saving record:', error);
            toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to save record' });
        }
    } else {
        console.error('Validation failed:', record.value);
        toast.add({ severity: 'error', summary: 'Error', detail: 'Please fill in all required fields' });
    }
}

function editRecord(rec) {
    record.value = { ...rec };
    isEditMode.value = true;
    recordDialog.value = true;
}

function findIndexById(id) {
    return records.value.findIndex((rec) => rec.id === id);
}

function confirmDeleteRecord(rec) {
    record.value = rec;
    deleteRecordDialog.value = true;
}

async function deleteRecord() {
    try {
        await api.delete(`/financial-records/${record.value.id}`);
        records.value = records.value.filter((val) => val.id !== record.value.id);
        deleteRecordDialog.value = false;
        record.value = {};
        toast.add({ severity: 'success', summary: 'Successful', detail: 'Record Deleted', life: 3000 });
    } catch (error) {
        console.error('Error deleting record:', error);
        toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to delete record', life: 3000 });
    }
}

function exportCSV() {
    dt.value.exportCSV();
}

function importCSV() {
    toast.add({ severity: 'info', summary: 'Info', detail: 'Not implemented yet', life: 3000 });
}

function confirmDeleteSelected() {
    deleteRecordsDialog.value = true;
}

async function deleteSelectedRecords() {
    try {
        const ids = selectedRecords.value.map(record => record.id);
        await api.post('/financial-records/delete-multiple', { ids });
        records.value = records.value.filter((val) => !selectedRecords.value.includes(val));
        deleteRecordsDialog.value = false;
        selectedRecords.value = null;
        toast.add({ severity: 'success', summary: 'Successful', detail: 'Records Deleted', life: 3000 });
    } catch (error) {
        console.error('Error deleting selected records:', error);
        toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to delete selected records' });
    }
}
</script>

<template>
    <div>
        <div class="font-semibold text-2xl mb-6">Financial Records</div>
        <div class="card">
            <Toolbar class="mb-6">
                <template #start>
                    <Button label="New" icon="pi pi-plus" severity="secondary" class="mr-2" @click="openNew" />
                    <Button label="Discard" icon="pi pi-trash" severity="secondary" @click="confirmDeleteSelected" :disabled="!selectedRecords || !selectedRecords.length" />
                </template>
                <template #end>
                    <Button label="Import from CSV file" icon="pi pi-download" severity="secondary" @click="importCSV($event)" class="mr-2" />
                    <Button label="Export to CSV file" icon="pi pi-upload" severity="secondary" @click="exportCSV($event)" />
                </template>
            </Toolbar>
            <DataTable
                ref="dt"
                v-model:selection="selectedRecords"
                :value="formattedRecords"
                dataKey="id"
                :paginator="true"
                :rows="10"
                :filters="filters"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                :rowsPerPageOptions="[5, 10, 25]"
                currentPageReportTemplate="Showing {first} to {last} of {totalRecords} records"
            >
                <template #header>
                    <div class="flex flex-wrap gap-2 items-center justify-between">
                        <h4 class="m-0">Manage Financial Records</h4>
                        <IconField>
                            <InputIcon>
                                <i class="pi pi-search" />
                            </InputIcon>
                            <InputText v-model="filters['global'].value" placeholder="Search Records..." />
                        </IconField>
                    </div>
                </template>
                <Column selectionMode="multiple" style="width: 3rem" :exportable="false"></Column>
                <Column field="date" header="Date" sortable style="min-width: 10rem"></Column>
                <Column field="type" header="Transaction Type" sortable style="min-width: 10rem"></Column>
                <Column field="category" header="Category" sortable style="min-width: 10rem"></Column>
                <Column field="amount" header="Amount" sortable style="min-width: 10rem"></Column>
                <Column :exportable="false" style="min-width: 12rem">
                    <template #body="slotProps">
                        <Button icon="pi pi-pencil" outlined rounded class="mr-2" @click="editRecord(slotProps.data)" />
                        <Button icon="pi pi-trash" outlined rounded severity="danger" @click="confirmDeleteRecord(slotProps.data)" />
                    </template>
                </Column>
            </DataTable>
        </div>
        <Dialog v-model:visible="recordDialog" :style="{ width: '450px' }" header="Financial Record Details" :modal="true">
            <div class="flex flex-col gap-6">
                <div>
                    <label for="date" class="block font-bold mb-3">Date</label>
                    <DatePicker id="date" v-model="record.date" :showIcon="true" optionLabel="label" placeholder="Select Date" fluid></DatePicker>
                </div>
                <div>
                    <label for="type" class="block font-bold mb-3">Transaction Type</label>
                    <Select id="type" v-model="record.type" :options="transactionTypes" optionLabel="label" placeholder="Select Type" required="true" fluid />
                </div>
                <div>
                    <label for="category" class="block font-bold mb-3">Category</label>
                    <Select id="category" v-model="record.category" :options="categories" optionLabel="label" placeholder="Select Category" required="true" fluid />
                </div>
                <div>
                    <label for="amount" class="block font-bold mb-3">Amount</label>
                    <InputText id="amount" v-model="record.amount" required="true" fluid placeholder="Input Amount"/>
                </div>
            </div>
            <template #footer>
                <Button label="Cancel" icon="pi pi-times" text @click="hideDialog" />
                <Button label="Save" icon="pi pi-check" @click="saveRecord" />
            </template>
        </Dialog>

        <Dialog v-model:visible="deleteRecordDialog" :style="{ width: '450px' }" header="Confirm" :modal="true">
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle !text-3xl" />
                <span v-if="record">Are you sure you want to discard <b>{{ record.date }}</b>?</span>
            </div>
            <template #footer>
                <Button label="No" icon="pi pi-times" text @click="deleteRecordDialog = false" />
                <Button label="Yes" icon="pi pi-check" @click="deleteRecord" />
            </template>
        </Dialog>

        <Dialog v-model:visible="deleteRecordsDialog" :style="{ width: '450px' }" header="Confirm" :modal="true">
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle !text-3xl" />
                <span v-if="record">Are you sure you want to discard the selected records?</span>
            </div>
            <template #footer>
                <Button label="No" icon="pi pi-times" text @click="deleteRecordsDialog = false" />
                <Button label="Yes" icon="pi pi-check" text @click="deleteSelectedRecords" />
            </template>
        </Dialog>
    </div>
</template>
