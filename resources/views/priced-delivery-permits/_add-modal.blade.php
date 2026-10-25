@php $p = $idPrefix ?? ''; @endphp
<!-- ===== مودال إضافة إذن تسليم مسعّر ===== -->
    <div id="{{ $p }}add-modal" class="fixed inset-0 z-50 items-start justify-center overflow-y-auto bg-foreground/45 p-4 backdrop-blur-sm" style="display:none" onclick="window.emdadat.closeOnBackdrop('{{ $p }}add-modal', event)">
    <div class="animate-zoom-in my-8 w-full max-w-3xl rounded-2xl border border-border bg-card shadow-card">
        <div class="flex items-center justify-between gap-4 rounded-t-2xl bg-brand px-5 py-4 text-primary-foreground">
            <div class="flex items-center gap-3">
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-primary-foreground/15">
                    <i data-lucide="file-plus" class="h-5 w-5"></i>
                </span>
                <div>
                    <h3 id="{{ $p }}modal-title" class="text-base font-bold">إضافة إذن تسليم مسعّر</h3>
                    <p id="{{ $p }}modal-subtitle" class="text-xs opacity-80">قم بإدخال بيانات إذن التسليم المسعّر الجديد</p>
                </div>
            </div>
            <button type="button" onclick="window.emdadat.closeModal('{{ $p }}add-modal')" class="grid h-9 w-9 place-items-center rounded-lg bg-primary-foreground/15 transition-colors hover:bg-primary-foreground/25" aria-label="إغلاق">
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>

        <form id="{{ $p }}add-form" class="p-5">
            @csrf
            <div class="grid gap-4 md:grid-cols-2">
                <div class="field-wrap space-y-1.5 md:col-span-2">
                    <label class="block text-[13px] font-semibold text-foreground/85">المستخدم المالك للإذن <span class="text-destructive">*</span></label>
                    <div id="{{ $p }}ss-user"></div>
                    <p class="text-[11px] text-muted-foreground">اختر المستخدم الذي سيتبع له هذا الإذن ويُكمِل باقي البيانات.</p>
                </div>

                <div class="field-wrap space-y-1.5 md:col-span-2">
                    <label class="block text-[13px] font-semibold text-foreground/85">رقم الإمداد <span class="text-destructive">*</span></label>
                    <div class="relative">
                        <i data-lucide="search" class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"></i>
                        <input id="{{ $p }}f-supply-no" name="supply_request_number" type="text" placeholder="اكتب رقم الإمداد للبحث..." autocomplete="off"
                            class="h-10 w-full rounded-lg border border-input bg-card ps-3 pe-9 text-[13px] text-foreground outline-none transition-shadow placeholder:text-muted-foreground/70 focus:border-primary focus:ring-2 focus:ring-ring/35" />
                    </div>
                    <p class="text-[11px] text-muted-foreground">اكتب رقم طلب الإمداد ثم اضغط Enter أو انقر خارج الحقل لكي يُبحث عنه تلقائيًا.</p>
                </div>

                <!-- بوكس بيانات طلب الإمداد -->
                <div id="{{ $p }}supply-info-box" class="hidden rounded-xl border border-gold/40 bg-gold/10 p-4 md:col-span-2">
                    <div class="mb-3 flex items-center gap-2">
                        <span class="grid h-7 w-7 place-items-center rounded-lg bg-gold/20 text-gold-foreground">
                            <i data-lucide="package-search" class="h-4 w-4"></i>
                        </span>
                        <p class="text-[13px] font-bold text-gold-foreground">بيانات طلب الإمداد</p>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <div class="space-y-1">
                            <label class="block text-[11px] font-semibold text-gold-foreground/80">نوع الطلب</label>
                            <input type="text" readonly id="{{ $p }}sr-type" class="h-9 w-full cursor-default rounded-lg border border-gold/30 bg-white/80 px-2.5 text-[12px] font-semibold text-foreground/90 focus:outline-none" />
                        </div>
                        <div class="space-y-1">
                            <label class="block text-[11px] font-semibold text-gold-foreground/80">رقم الإمداد</label>
                            <input type="text" readonly id="{{ $p }}sr-number" class="h-9 w-full cursor-default rounded-lg border border-gold/30 bg-white/80 px-2.5 text-[12px] font-semibold text-foreground/90 focus:outline-none" />
                        </div>
                        <div class="space-y-1">
                            <label class="block text-[11px] font-semibold text-gold-foreground/80">المستشفى</label>
                            <input type="text" readonly id="{{ $p }}sr-hospital" class="h-9 w-full cursor-default rounded-lg border border-gold/30 bg-white/80 px-2.5 text-[12px] font-semibold text-foreground/90 focus:outline-none" />
                        </div>
                        <div class="space-y-1">
                            <label class="block text-[11px] font-semibold text-gold-foreground/80">القسم</label>
                            <input type="text" readonly id="{{ $p }}sr-department" class="h-9 w-full cursor-default rounded-lg border border-gold/30 bg-white/80 px-2.5 text-[12px] font-semibold text-foreground/90 focus:outline-none" />
                        </div>
                        <div class="space-y-1">
                            <label class="block text-[11px] font-semibold text-gold-foreground/80">الجهة المالية</label>
                            <input type="text" readonly id="{{ $p }}sr-link" class="h-9 w-full cursor-default rounded-lg border border-gold/30 bg-white/80 px-2.5 text-[12px] font-semibold text-foreground/90 focus:outline-none" />
                        </div>
                        <div class="space-y-1">
                            <label class="block text-[11px] font-semibold text-gold-foreground/80">الفئة المالية</label>
                            <input type="text" readonly id="{{ $p }}sr-category" class="h-9 w-full cursor-default rounded-lg border border-gold/30 bg-white/80 px-2.5 text-[12px] font-semibold text-foreground/90 focus:outline-none" />
                        </div>
                        <div class="space-y-1">
                            <label class="block text-[11px] font-semibold text-gold-foreground/80">قيمة الطلب</label>
                            <input type="text" readonly id="{{ $p }}sr-amount" class="h-9 w-full cursor-default rounded-lg border border-gold/30 bg-white/80 px-2.5 text-[12px] font-bold text-primary focus:outline-none" />
                        </div>
                        <div class="space-y-1">
                            <label class="block text-[11px] font-semibold text-gold-foreground/80">تاريخ الطلب</label>
                            <input type="text" readonly id="{{ $p }}sr-date" class="h-9 w-full cursor-default rounded-lg border border-gold/30 bg-white/80 px-2.5 text-[12px] font-semibold text-foreground/90 focus:outline-none" />
                        </div>
                        <div class="space-y-1">
                            <label class="block text-[11px] font-semibold text-gold-foreground/80">عدد أذونات التسعير لنفس طلب الإمداد</label>
                            <input type="text" readonly id="{{ $p }}sr-permits-count" class="h-9 w-full cursor-default rounded-lg border border-gold/30 bg-white/80 px-2.5 text-[12px] font-bold text-success focus:outline-none" />
                        </div>
                        <div class="space-y-1">
                            <label class="block text-[11px] font-semibold text-gold-foreground/80">المبلغ المورد</label>
                            <input type="text" readonly id="{{ $p }}sr-delivered" class="h-9 w-full cursor-default rounded-lg border border-gold/30 bg-white/80 px-2.5 text-[12px] font-bold text-success focus:outline-none" />
                        </div>
                        <div class="space-y-1">
                            <label class="block text-[11px] font-semibold text-gold-foreground/80">المتبقى</label>
                            <input type="text" readonly id="{{ $p }}sr-remaining" class="h-9 w-full cursor-default rounded-lg border border-gold/30 bg-white/80 px-2.5 text-[12px] font-bold text-success focus:outline-none" />
                        </div>
                    </div>
                </div>

                <div class="field-wrap space-y-1.5">
                    <label class="block text-[13px] font-semibold text-foreground/85">قيمة إذن التسليم <span class="text-destructive">*</span></label>
                    <input id="{{ $p }}f-amount" name="delivery_amount" type="number" min="0" step="0.01" placeholder="مثال: 32000"
                        class="h-10 w-full rounded-lg border border-input bg-card px-3 text-[13px] text-foreground outline-none transition-shadow placeholder:text-muted-foreground/70 focus:border-primary focus:ring-2 focus:ring-ring/35" />
                </div>

                <div class="field-wrap space-y-1.5">
                    <label class="block text-[13px] font-semibold text-foreground/85">رقم إذن التسليم <span class="text-destructive">*</span></label>
                    <input id="{{ $p }}f-note-no" name="delivery_permit_number" type="text" placeholder="مثال: DN-2026-520"
                        class="h-10 w-full rounded-lg border border-input bg-card px-3 text-[13px] text-foreground outline-none transition-shadow placeholder:text-muted-foreground/70 focus:border-primary focus:ring-2 focus:ring-ring/35" />
                </div>

                <div class="field-wrap space-y-1.5">
                    <label class="block text-[13px] font-semibold text-foreground/85">تاريخ إصدار الإذن <span class="text-destructive">*</span></label>
                    <input id="{{ $p }}f-issue-date" name="issue_date" type="date"
                        class="h-10 w-full rounded-lg border border-input bg-card px-3 text-[13px] text-foreground outline-none transition-shadow focus:border-primary focus:ring-2 focus:ring-ring/35" />
                </div>

                <div class="field-wrap space-y-1.5">
                    <label class="block text-[13px] font-semibold text-foreground/85">اسم الشركة <span class="text-destructive">*</span></label>
                    <div id="{{ $p }}ss-company"></div>
                </div>

                <div class="field-wrap space-y-1.5">
                    <label class="block text-[13px] font-semibold text-foreground/85">تاريخ تحرير الاستمارة/الصرف</label>
                    <input id="{{ $p }}f-form-date" name="form_date" type="date"
                        class="h-10 w-full rounded-lg border border-input bg-card px-3 text-[13px] text-foreground outline-none transition-shadow focus:border-primary focus:ring-2 focus:ring-ring/35" />
                </div>

                <div class="field-wrap space-y-1.5">
                    <label class="block text-[13px] font-semibold text-foreground/85">حالة إذن التسليم المسعّر <span class="text-destructive">*</span></label>
                    <div class="relative">
                        <select id="{{ $p }}f-status" name="status" class="h-10 w-full appearance-none rounded-lg border border-input bg-card ps-3 pe-9 text-[13px] text-foreground outline-none transition-shadow focus:border-primary focus:ring-2 focus:ring-ring/35">
                            <option value="">اختر...</option>
                            <option value="تم الصرف">تم الصرف</option>
                            <option value="لم يتم الصرف">لم يتم الصرف</option>
                        </select>
                        <i data-lucide="chevron-down" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"></i>
                    </div>
                </div>
            </div>

            <div class="mt-5 flex flex-wrap items-center justify-between gap-2 rounded-b-2xl border-t border-border bg-card pt-4">
                <label class="inline-flex cursor-pointer items-center gap-2 select-none">
                    <input id="{{ $p }}f-fully-delivered" name="fully_delivered" type="checkbox"
                        class="h-4 w-4 accent-primary" />
                    <span class="text-[13px] font-semibold text-foreground/85">تم التوريد بالكامل</span>
                </label>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" onclick="window.emdadat.closeModal('{{ $p }}add-modal')" class="h-10 rounded-lg border border-border bg-card px-5 text-[13px] font-bold text-muted-foreground transition-colors hover:bg-accent">إلغاء</button>
                    <button type="submit" id="{{ $p }}save-btn" class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand px-5 text-[13px] font-bold text-primary-foreground shadow-soft transition-transform hover:-translate-y-0.5">
                        <i data-lucide="save" class="h-4 w-4"></i>
                        حفظ الإذن
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
