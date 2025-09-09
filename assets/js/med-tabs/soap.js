// assets/js/med-tabs/soap.js
document.addEventListener('DOMContentLoaded', () => {
  const BASE = (window.App && App.BASE_URL) || (() => {
    const s = document.querySelector('script[src*="med-tabs/soap.js"]');
    if (s){ const u=new URL(s.src,location.origin); const p=u.pathname; const i=p.indexOf('/assets/'); if(i>0) return p.substring(0,i)+'/'; }
    const parts=location.pathname.split('/').filter(Boolean); return parts.length?`/${parts[0]}/`:'/';
  })();

  const els = {
    search: document.getElementById('soapSearch'),
    table:  document.getElementById('soapTable').querySelector('tbody'),
    btnAdd: document.getElementById('btnAddSOAP'),
  };
  let currentPetId = 0;
  let rows = [];

  function rowHTML(r){
    const short = s => (s||'').length>60 ? (s.slice(0,60)+'…') : (s||'');
    return `<tr data-id="${r.id}">
      <td>${r.record_date ? new Date(r.record_date).toLocaleString() : ''}</td>
      <td>${r.weight_kg ?? ''}</td>
      <td>${r.temp_c ?? ''}</td>
      <td>${r.pulse_bpm ?? ''}</td>
      <td>${r.resp_cpm ?? ''}</td>
      <td>${short(r.subjective)}</td>
      <td>${short(r.objective)}</td>
      <td>${short(r.assessment)}</td>
      <td>${short(r.plan)}</td>
      <td class="t-actions">
        <button class="btn btn-icon act-edit" title="Edit"><i class="fa-solid fa-pen"></i></button>
        <button class="btn btn-icon act-del" title="Delete"><i class="fa-solid fa-trash"></i></button>
      </td>
    </tr>`;
  }
  function render(data){
    els.table.innerHTML = data.length ? data.map(rowHTML).join('') : `<tr><td colspan="10" class="muted">No records.</td></tr>`;
  }
  function filter(){
    const q = (els.search.value||'').toLowerCase();
    if(!q) { render(rows); return; }
    const f = rows.filter(r => JSON.stringify(r).toLowerCase().includes(q));
    render(f);
  }

  async function loadSOAP(){
    if(!currentPetId) { render([]); return; }
    const r = await fetch(`${BASE}api/staffs/medical/soap.php?pet_id=${currentPetId}`);
    const j = await r.json();
    rows = (j.ok && Array.isArray(j.items)) ? j.items : [];
    render(rows);
  }

  function openModal(existing){
    Swal.fire({
      title: existing ? 'Edit S.O.A.P.' : 'Add S.O.A.P.',
      html: `
        <div class="grid" style="display:grid;grid-template-columns:repeat(2,1fr);gap:.5rem;text-align:left">
          <label>Weight(kg)<input id="w" class="swal2-input" style="width:100%" value="${existing?.weight_kg ?? ''}"></label>
          <label>Temp(°C)<input id="t" class="swal2-input" style="width:100%" value="${existing?.temp_c ?? ''}"></label>
          <label>Pulse(bpm)<input id="p" class="swal2-input" style="width:100%" value="${existing?.pulse_bpm ?? ''}"></label>
          <label>Resp(cpm)<input id="r" class="swal2-input" style="width:100%" value="${existing?.resp_cpm ?? ''}"></label>
        </div>
        <textarea id="subj" class="swal2-textarea" placeholder="Subjective">${existing?.subjective ?? ''}</textarea>
        <textarea id="obj"  class="swal2-textarea" placeholder="Objective">${existing?.objective ?? ''}</textarea>
        <textarea id="ass"  class="swal2-textarea" placeholder="Assessment">${existing?.assessment ?? ''}</textarea>
        <textarea id="plan" class="swal2-textarea" placeholder="Plan">${existing?.plan ?? ''}</textarea>
      `,
      focusConfirm:false,
      showCancelButton:true,
      confirmButtonText: existing ? 'Save' : 'Create',
      preConfirm: ()=>{
        return {
          weight_kg:  document.getElementById('w').value.trim(),
          temp_c:     document.getElementById('t').value.trim(),
          pulse_bpm:  document.getElementById('p').value.trim(),
          resp_cpm:   document.getElementById('r').value.trim(),
          subjective: document.getElementById('subj').value.trim(),
          objective:  document.getElementById('obj').value.trim(),
          assessment: document.getElementById('ass').value.trim(),
          plan:       document.getElementById('plan').value.trim(),
        };
      }
    }).then(async res=>{
      if(!res.isConfirmed) return;
      const payload = res.value;
      const fd = new FormData();
      fd.append('pet_id', String(currentPetId));
      Object.entries(payload).forEach(([k,v])=>fd.append(k, v));

      let url = `${BASE}api/staffs/medical/soap.php`;
      if (existing?.id) fd.append('id', String(existing.id));

      const resp = await fetch(url, { method:'POST', body: fd });
      const j = await resp.json();
      if(!j.ok) { Swal.fire('Error', j.error || 'Save failed', 'error'); return; }
      await loadSOAP();
      Swal.fire('Saved','Record persisted.','success');
    });
  }

  els.btnAdd.addEventListener('click', ()=> openModal(null));
  els.search.addEventListener('input', ()=>filter());
  document.getElementById('soapTable').addEventListener('click', e=>{
    const tr = e.target.closest('tr');
    if(!tr) return;
    const row = rows.find(x=>String(x.id)===tr.dataset.id);
    if(e.target.closest('.act-edit')) { openModal(row); }
    if(e.target.closest('.act-del')) {
      Swal.fire({title:'Delete?',text:'This action cannot be undone.',icon:'warning',showCancelButton:true})
      .then(async r=>{
        if(!r.isConfirmed) return;
        const resp = await fetch(`${BASE}api/staffs/medical/soap.php?id=${row.id}`, { method:'DELETE' });
        const j = await resp.json();
        if(!j.ok){ Swal.fire('Error', j.error || 'Delete failed', 'error'); return; }
        await loadSOAP();
        Swal.fire('Deleted','Record removed.','success');
      });
    }
  });

  // listen when a pet is selected (from left lists)
  document.addEventListener('pet:selected', (e)=>{
    currentPetId = e.detail.petId || 0;
    loadSOAP().catch(()=>{ rows=[]; render([]); });
  });
});
