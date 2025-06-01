import { Component } from '@angular/core';
import {SidebarItemComponent} from '../sidebar-item/sidebar-item.component';

@Component({
  selector: 'app-contacts',
  imports: [
    SidebarItemComponent
  ],
  standalone: true,
  templateUrl: './contacts.component.html',
  styleUrl: './contacts.component.css'
})
export class ContactsComponent {

}
