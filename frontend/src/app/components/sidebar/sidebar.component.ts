import { Component } from '@angular/core';
import {SearchboxComponent} from './searchbox/searchbox.component';
import {MenuComponent} from './menu/menu.component';
import {MinipostsComponent} from './miniposts/miniposts.component';
import {ContactsComponent} from './contacts/contacts.component';
import {FooterComponent} from './footer/footer.component';

@Component({
  selector: 'sidebar',
  imports: [
    SearchboxComponent,
    MenuComponent,
    MinipostsComponent,
    ContactsComponent,
    FooterComponent
  ],
  standalone: true,
  templateUrl: './sidebar.component.html',
  styleUrl: './sidebar.component.css'
})
export class SidebarComponent {

}
