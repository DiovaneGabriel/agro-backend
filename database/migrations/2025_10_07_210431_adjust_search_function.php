<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{

    private function dropFunction()
    {
        DB::unprepared("do $$
declare
    r record;
begin
    for r in
        select p.oid::regprocedure as fqn
        from pg_proc p
        join pg_namespace n on n.oid = p.pronamespace
        where p.proname = 'get_products_ai_page'
          and n.nspname = 'public'
    loop
        execute format('drop function if exists %s cascade;', r.fqn);
    end loop;
end$$;");
    }

    public function up(): void
    {
        $this->dropFunction();

        DB::unprepared("create or replace function public.get_products_ai_page(
    p_rows_per_page int,
    p_page int,
    p_search text,
    p_culture_id cultures.id%type,
    p_class_id classes.id%type,
    p_prague_id pragues.id%type,
    p_prague_common_name_id prague_common_names.id%type,
    p_action_mechanism_id action_mechanisms.id%type,
    p_active_ingredient_id active_ingredients.id%type,
    p_action_mode_id action_modes.id%type,
    p_registration_holder_id products.registration_holder_id%type,--essa é nova
    p_toxicological_class_id toxicological_classes.id%type,
    p_environmental_class_id environmental_classes.id%type
)
returns table (
  id products.id%type,
  brand_id product_brands.id%type,
  brand_name product_brands.name%type,
  active_ingredients text,
  action_mechanism action_mechanisms.name%type,
  total_count bigint
)
language sql
stable
as $$
with 
_action_mechanisms as (
select pai.product_id,
       string_agg(distinct am.name,' + ' order by am.name) action_mechanism_names
  from product_active_ingredients pai
  join active_ingredient_action_mechanisms aiam on aiam.active_ingredient_id = pai.active_ingredient_id
  join product_classes pc on pc.class_id = aiam.class_id
                         and pc.product_id = pai.product_id
  join action_mechanisms am on am.id = aiam.action_mechanism_id
 group by pai.product_id
),
_products as (
  select
      p.id,
      pb.id as brand_id,
      pb.name as brand_name,
      string_agg(
        concat(ai.name,' (',pai.concentration,')'),
        ' + '
        order by
          coalesce(
            (replace(substring(pai.concentration from '\d+(?:[.,]\d+)?'), ',', '.'))::numeric,
            -1
          ) desc,
          ai.name
      ) as active_ingredients,
      am.action_mechanism_names
  from products p
  join product_brands pb on pb.product_id = p.id
  left join product_active_ingredients pai on pai.product_id = p.id
  left join active_ingredients ai on ai.id = pai.active_ingredient_id
  left join _action_mechanisms am on am.product_id = p.id
  group by p.id,
           pb.id,
           pb.name,
           am.action_mechanism_names
)

select
    p.id,
    p.brand_id,
    p.brand_name,
    p.active_ingredients,
    p.action_mechanism_names,
    count(*) over() as total_count
from _products p
where length(p_search) >= 3
  and (
        p.brand_name ilike concat('%', replace(p_search,' ','%'), '%')
     or p.active_ingredients ilike concat('%', replace(p_search,' ','%'), '%')
  )
  and (p_culture_id is null or exists (
        select 1
        from product_cultures pc
        where pc.culture_id = p_culture_id
          and p.id = pc.product_id
  ))
  and (p_class_id is null or exists (
        select 1
        from product_classes pc
        where pc.class_id = p_class_id
          and p.id = pc.product_id
  ))
  and (p_prague_id is null or exists (
        select 1
        from product_pragues pp
        where pp.prague_id = p_prague_id
          and p.id = pp.product_id
  ))
  and (p_prague_common_name_id is null or exists (
        select 1
        from product_pragues pp
        join prague_common_names pcn on pcn.prague_id = pp.prague_id
        where pcn.id = p_prague_common_name_id
          and p.id = pp.product_id
  ))
  and (p_action_mechanism_id is null or exists (
        select 1
        from product_active_ingredients pai
        join active_ingredient_action_mechanisms aiam on aiam.active_ingredient_id = pai.active_ingredient_id
        join product_classes pc on pc.class_id = aiam.class_id
                               and pc.product_id = pai.product_id
       where aiam.action_mechanism_id = p_action_mechanism_id
         and p.id = pai.product_id
  ))
  and (p_active_ingredient_id is null or exists (
        select 1
        from product_active_ingredients pai
        where pai.active_ingredient_id = p_active_ingredient_id
          and p.id = pai.product_id
  ))
  and (p_action_mode_id is null or exists (
        select 1
        from product_action_modes pam
        where pam.action_mode_id = p_action_mode_id
          and p.id = pam.product_id
  ))
  and (p_registration_holder_id is null or exists (
        select 1
        from products p1
        where p1.registration_holder_id = p_registration_holder_id
          and p.id = p1.id
  ))
  and (p_toxicological_class_id is null or exists (
        select 1
        from products p1
        where p1.toxicological_class_id = p_toxicological_class_id
          and p.id = p1.id
  ))
  and (p_environmental_class_id is null or exists (
        select 1
        from products p1
        where p1.environmental_class_id = p_environmental_class_id
          and p.id = p1.id
  ))
order by p.brand_name
offset p_rows_per_page * (p_page - 1)
limit p_rows_per_page;
$$;");
    }

    public function down(): void
    {
        $this->dropFunction();
    }
};
