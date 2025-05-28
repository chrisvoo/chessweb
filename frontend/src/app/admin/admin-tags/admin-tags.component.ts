import { Component } from '@angular/core';
import { TableModule } from 'primeng/table';
import { Button } from 'primeng/button';
import { IconField } from 'primeng/iconfield';
import { InputIcon } from 'primeng/inputicon';
import { InputText } from 'primeng/inputtext';
import { Paginator } from 'primeng/paginator';
import { TooltipModule } from 'primeng/tooltip';

@Component({
  selector: 'app-admin-tags',
  imports: [TableModule, Button, IconField, InputIcon, InputText, Paginator, TooltipModule],
  templateUrl: './admin-tags.component.html',
  standalone: true,
  styleUrl: './admin-tags.component.css'
})
export class AdminTagsComponent {

}
