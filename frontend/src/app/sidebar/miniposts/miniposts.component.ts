import { Component } from '@angular/core';
import { SidebarItemComponent } from '../sidebar-item/sidebar-item.component';

@Component({
  selector: 'app-miniposts',
  imports: [
    SidebarItemComponent
  ],
  standalone: true,
  templateUrl: './miniposts.component.html',
  styleUrl: './miniposts.component.css'
})
export class MinipostsComponent {

}
