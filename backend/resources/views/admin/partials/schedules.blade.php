@php
    $weekdays = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
    $scheduleRows = old('schedules', $record->exists ? $record->schedules->map(fn($slot)=>['day_of_week'=>$slot->day_of_week,'opens_at'=>substr($slot->opens_at,0,5),'closes_at'=>substr($slot->closes_at,0,5),'is_active'=>$slot->is_active])->all() : []);
@endphp
<section class="content-panel mb-4">
    <h2>Consultation hours</h2><p>Times use the chamber timezone selected above. Add multiple slots when needed; active slots on the same weekday must not overlap.</p>
    <input type="hidden" name="schedules_present" value="1">
    <div class="table-responsive"><table class="table align-middle schedule-table"><thead><tr><th scope="col">Weekday</th><th scope="col">Opens</th><th scope="col">Closes</th><th scope="col">Active</th><th scope="col">Action</th></tr></thead>
        <tbody id="schedule-rows">
        @foreach($scheduleRows as $index=>$slot)
            <tr data-schedule-row>
                <td data-label="Weekday"><select class="form-select" name="schedules[{{ $index }}][day_of_week]" aria-label="Weekday">@foreach($weekdays as $day=>$label)<option value="{{ $day }}" @selected((string)$slot['day_of_week'] === (string)$day)>{{ $label }}</option>@endforeach</select></td>
                <td data-label="Opens"><input class="form-control" type="time" name="schedules[{{ $index }}][opens_at]" value="{{ $slot['opens_at'] }}" aria-label="Opening time" required></td>
                <td data-label="Closes"><input class="form-control" type="time" name="schedules[{{ $index }}][closes_at]" value="{{ $slot['closes_at'] }}" aria-label="Closing time" required></td>
                <td data-label="Active"><input type="hidden" name="schedules[{{ $index }}][is_active]" value="0"><input class="form-check-input" type="checkbox" name="schedules[{{ $index }}][is_active]" value="1" @checked($slot['is_active']) aria-label="Time slot active"></td>
                <td><button class="btn btn-sm btn-outline-danger" type="button" data-remove-schedule aria-label="Remove time slot">Remove</button></td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
    <button class="btn btn-outline-success" type="button" id="add-schedule">Add time slot</button>
    <template id="schedule-row-template">
        <tr data-schedule-row>
            <td data-label="Weekday"><select class="form-select" name="schedules[__INDEX__][day_of_week]" aria-label="Weekday">@foreach($weekdays as $day=>$label)<option value="{{ $day }}">{{ $label }}</option>@endforeach</select></td>
            <td data-label="Opens"><input class="form-control" type="time" name="schedules[__INDEX__][opens_at]" aria-label="Opening time" required></td>
            <td data-label="Closes"><input class="form-control" type="time" name="schedules[__INDEX__][closes_at]" aria-label="Closing time" required></td>
            <td data-label="Active"><input type="hidden" name="schedules[__INDEX__][is_active]" value="0"><input class="form-check-input" type="checkbox" name="schedules[__INDEX__][is_active]" value="1" checked aria-label="Time slot active"></td>
            <td><button class="btn btn-sm btn-outline-danger" type="button" data-remove-schedule aria-label="Remove time slot">Remove</button></td>
        </tr>
    </template>
</section>
